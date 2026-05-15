<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\CreateAdminRequest;
use App\Http\Requests\CreateStudentRequest;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UserFirstLoginRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Imports\UsersImport;
use App\Imports\UsersImportSync;
use App\Models\Room;
use App\Models\User;
use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    use ApiResponder;

    /**
     * Get all students data
     */
    #[Group('User')]
    public function getStudents()
    {
        $role = Auth::user()->role;
        $role = $role == UserRole::TEACHER->value ? 'mentor' : 'counselor';
        $students = User::with(['room', 'mentor', 'lastmoodres'])->whereRole(UserRole::STUDENT->value)->where($role.'_id', Auth::id())->get();

        return $this->success(UserResource::collection($students));
    }

    /**
     * Get latest 3 user
     */
    #[Group('User')]
    public function getLatestUser()
    {
        $users = Auth::user()->school->users()
            ->latest()
            ->limit(3)
            ->get();

        return $this->success(UserResource::collection($users));
    }

    /**
     * Get user count created today
     */
    #[Group('User')]
    public function getTodayUser()
    {
        $users = Auth::user()->school->users()->whereDate('created_at', now())->count();

        return $this->success(['count' => (int) $users]);
    }

    /**
     * Create new admin of the school
     */
    #[Group('User')]
    public function adminCreate(CreateAdminRequest $request)
    {
        $user = User::create($request->all());

        return $this->success(new UserResource($user));
    }

    /**
     * Create new user at the school
     */
    #[Group('User')]
    public function store(CreateUserRequest $request)
    {
        $user = User::create($request->all());

        return $this->success(new UserResource($user));
    }

    /**
     * Delete user
     */
    #[Group('User')]
    public function destroy(User $user)
    {
        Gate::allowIf(function (User $auth) use ($user) {
            if ($auth->role == UserRole::ADMIN->value && ! in_array($user->role, [UserRole::SUPER->value, UserRole::ADMIN->value])) {
                return $auth->school_id == $user->school_id;
            } elseif ($auth->role == UserRole::SUPER->value) {
                return $user->role == UserRole::ADMIN->value;
            }

            return false;
        });
        $copy = $user->toArray();
        $user->delete();

        return $this->delete($copy);
    }

    /**
     * Get all users data at one school
     */
    #[Group('User')]
    public function getUsers()
    {
        $users = User::with(['room', 'mentor'])->whereSchoolId(Auth::user()->school_id)->get();

        return $this->success(UserResource::collection($users));
    }

    /**
     * Get all users data by its type
     *
     * Jika dilakukan oleh Super Admin maka semua data didalam Sistem, selainnya maka hanya di sekolah tersebut
     */
    #[Group('User')]
    public function getUsersByType(string $type)
    {
        Gate::allowIf(function (User $user) {
            return $user->role != UserRole::STUDENT->value;
        });
        if (Auth::user()->role == UserRole::SUPER->value) {
            $users = User::with('school')->whereRole($type)->get();
        } else {

            $users = User::whereRole($type)->whereSchoolId(Auth::user()->school_id)->get();
        }

        return $this->success(UserResource::collection($users));
    }

    /**
     * Get user data by username
     */
    #[Group('User')]
    public function getUserDetail(string $username)
    {
        $user = User::with(['school', 'room', 'mentor', 'counselor'])->where('username', $username)->first();

        return $this->success(new UserResource($user));
    }

    /**
     * Get template for bulk create
     */
    #[Group('User')]
    public function getTemplate()
    {
        return $this->success(['link' => config('app.url').'/templates/Template%20Siswa.xlsx']);
    }

    /**
     * Update user profile on first login
     */
    #[Group('User')]
    public function profile(UserFirstLoginRequest $request)
    {
        Auth::user()->update($request->all());

        return $this->success(new UserResource(Auth::user()), 'Success update user profile');
    }

    /**
     * Edit user data (by admin)
     *
     * Kalau yang diedit adalah siswa maka butuh room_id (berupa 8 karakter kode kelas) dan mentor_id (berupa NIP Guru Wali). Jika admin yang diedit maka butuh school_id. Selainnya hanya username, phone, identifier, name, dan password
     */
    #[Group('User')]
    public function edit(UpdateUserRequest $request, User $user)
    {
        Gate::allowIf(function (User $auth) use ($user) {
            return ($auth->role == UserRole::ADMIN->value && $auth->school_id == $user->school_id) || ($auth->role == UserRole::SUPER->value);
        });

        $data = $request->validated();

        // Resolve room_id dari code ke ID (khusus student)
        if ($user->role === UserRole::STUDENT->value) {
            if (! empty($data['room_id'])) {
                $data['room_id'] = Room::whereCode($data['room_id'])->value('id');
            }
            if (! empty($data['mentor_id'])) {
                $data['mentor_id'] = User::whereIdentifier($data['mentor_id'])->value('id');
            }
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return $this->success(new UserResource($user), 'Success update user profile');
    }

    /**
     * Update user profile
     */
    #[Group('User')]
    public function editProfile(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'username' => "string|unique:users,username,{$user->id}",
            'phone' => "string|unique:users,phone,{$user->id}",
        ]);
        Auth::user()->update($request->all());

        return $this->success(new UserResource(Auth::user()), 'Success update user profile');
    }

    /**
     * Create student bulk with excel
     */
    #[Group('User')]
    public function bulkCreate(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx',
        ]);
        $file = $request->file('file');
        if ($file->getSize() > 30 * 1024) {
            Excel::import(new UsersImport(Auth::user()->school_id), $file);

            return $this->success(null, 'Your data will insert async');
        } else {
            $import = new UsersImportSync(Auth::user()->school_id);
            Excel::import($import, $file);
            $data = $import->getInsertedUsers();
            $count = $data->count();
            if ($count) {
                return $this->success(compact(['data', 'count']));
            } else {
                return $this->error('File bermasalah');
            }
        }
    }

    /**
     * Create single student
     */
    #[Group('User')]
    public function studentCreate(CreateStudentRequest $request)
    {
        $student = User::create($request->all());

        return $this->created(new UserResource($student));
    }
}
