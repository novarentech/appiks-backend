<?php

use Dedoc\Scramble\Scramble;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'docs');
// Route::redirect('docs', 'https://appiks.id');
// Route::get('reset', function () {
//     Artisan::call('migrate:fresh-backup');

//     return 'reset success';
// });
Scramble::registerUiRoute('docs');
Scramble::registerJsonSpecificationRoute('api/docs.json');
Route::get('reset', function (Request $request) {
    $expectedUser = env('RESET_AUTH_USER', 'novaren');
    $expectedPass = env('RESET_AUTH_PASSWORD', 'secret');

    if ($request->getUser() !== $expectedUser || $request->getPassword() !== $expectedPass) {
        return response('Unauthorized', 401, [
            'WWW-Authenticate' => 'Basic realm="Reset Database"',
        ]);
    }

    $command = PHP_OS_FAMILY === 'Windows'
        ? 'start "" /B php artisan migrate:fresh --seed --seeder=DemoCaseSeeder --force'
        : 'php artisan migrate:fresh --seed --seeder=DemoCaseSeeder --force > /dev/null 2>&1 &';

    pclose(popen('cd ' . escapeshellarg(base_path()) . ' && ' . $command, 'r'));

    return response()->json([
        'status' => 'success',
        'message' => 'Database reset initiated in the background.',
    ]);
});
