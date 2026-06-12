<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('psychologist_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->unique()->constrained()->cascadeOnDelete();
            $table->string('str_number')->unique()->index();
            $table->string('specialization')->nullable();
            $table->string('institution_name')->index();
            $table->string('phone_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'super',
                'admin',
                'headteacher',
                'teacher',
                'counselor',
                'student',
                'psychologist'
            ])->default('student')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psychologist_profiles');
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'super',
                'admin',
                'headteacher',
                'teacher',
                'counselor',
                'student'
            ])->default('student')->change();
        });
    }
};
