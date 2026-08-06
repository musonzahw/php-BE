<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('location_logs');
        Schema::dropIfExists('fuel_records');
        Schema::dropIfExists('maintenance_records');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('personal_access_tokens');
    }

    public function down(): void
    {
    }
};
