<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL specific raw query to update enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'fat', 'departemen', 'manager') DEFAULT 'departemen'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'fat', 'departemen') DEFAULT 'departemen'");
    }
};
