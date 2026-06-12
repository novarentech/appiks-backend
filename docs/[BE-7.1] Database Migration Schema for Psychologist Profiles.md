# [BE-7.1] Database Migration Schema for Psychologist Profiles

## Overview

To enable secure external referrals to medical professionals from the Puskesmas, we extend the platform's role hierarchy and introduce a dedicated mapping profile structure. This ensures external psychologists can access the system without compromising the data isolation of internal school structures.

---

## Schema Modifications

### 1. `users` Table Modifications

We update the database-level `role` enum in the `users` table to add the new `psychologist` role.

- **PHP Enum (`app/Enums/UserRole.php`)**:
  ```php
  enum UserRole: string
  {
      ...
      case PSYCHOLOGIST = 'psychologist';
  }
  ```

- **Migration (`2026_06_12_120001_add_psychologist_to_user_roles.php`)**:
  Alters the `role` enum in the MySQL database:
  ```php
  Schema::table('users', function (Blueprint $table) {
      $table->enum('role', [
          'super', 'admin', 'headteacher', 'teacher', 'counselor', 'student', 'psychologist'
      ])->default('student')->change();
  });
  ```

---

### 2. New Table: `psychologist_profiles`

This table maps professional and medical metadata specific to Partner Psychologists back to their core user record.

| Column             | Type      | Description                                                 |
| ------------------ | --------- | ----------------------------------------------------------- |
| `id`               | bigint    | Primary key                                                 |
| `user_id`          | bigint    | FK to `users` table (unique, indexed, cascade on delete)    |
| `str_number`       | string    | STR (Surat Tanda Registrasi) number (unique, indexed)        |
| `specialization`   | string    | Specialization area (nullable)                              |
| `institution_name` | string    | Associated institution / clinic / Puskesmas name (indexed)   |
| `phone_number`     | string    | Psychologist's contact number (nullable)                    |
| `is_active`        | boolean   | Active status for platform access (default: `true`)         |
| `created_at`       | timestamp | Creation time                                               |
| `updated_at`       | timestamp | Last updated time                                           |
| `deleted_at`       | timestamp | Soft delete timestamp                                       |

---

## Model Implementation & Relationships

The following Eloquent associations connect core user credentials to professional psychologist profiles:

### `User` Model Relationship (`app/Models/User.php`)
```php
public function psychologistProfile()
{
    return $this->hasOne(PsychologistProfile::class);
}
```

### `PsychologistProfile` Model (`app/Models/PsychologistProfile.php`)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PsychologistProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $table = 'psychologist_profiles';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
```
