<?php

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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('role')->default('kasikas')->after('password'); // kasikas, finali or admin
            $table->foreignId('location_id')->nullable()->after('role')->constrained('locations');
            $table->string('photo')->nullable()->after('location_id'); // profile picture
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
            $table->dropColumn(['username', 'role', 'photo']);
        });
    }
};
