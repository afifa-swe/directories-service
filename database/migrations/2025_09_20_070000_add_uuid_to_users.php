<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds a new uuid column to the users table, fills it for existing
     * records and makes it NOT NULL and UNIQUE. We do not drop the old numeric id
     * to avoid breaking foreign keys; instead the application will switch to use
     * the uuid primary key in the User model.
     *
     * Note: modifying primary keys and foreign keys is a complex operation. This
     * migration takes a conservative approach: add uuid column, populate it and
     * enforce uniqueness. Adjust or extend in a controlled maintenance window
     * if you need to convert existing foreign keys to reference uuid instead.
     *
     * @return void
     */
    public function up()
    {
        // Add uuid column nullable
        Schema::table('users', function (Blueprint $table) {
            $table->string('uuid', 36)->nullable()->after('id');
        });

        // Populate uuid for existing users using PHP (avoid DB uuid functions)
        $users = DB::table('users')->select('id')->get();
        foreach ($users as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['uuid' => (string) Str::uuid()]);
        }

        // Ensure any remaining null uuids are filled
        DB::table('users')->whereNull('uuid')->update(['uuid' => (string) Str::uuid()]);

        // Create unique index on uuid
        Schema::table('users', function (Blueprint $table) {
            $table->unique('uuid', 'users_uuid_unique');
        });

        // Set uuid column NOT NULL where supported (skip for sqlite)
        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['pgsql'])) {
            DB::statement('ALTER TABLE users ALTER COLUMN uuid SET NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_uuid_unique');
            $table->dropColumn('uuid');
        });
    }
};
