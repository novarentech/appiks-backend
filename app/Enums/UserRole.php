<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER = 'super';
    case ADMIN = 'admin';
    case HEADTEACHER = 'headteacher';
    case TEACHER = 'teacher';
    case COUNSELOR = 'counselor';
    case STUDENT = 'student';
}
