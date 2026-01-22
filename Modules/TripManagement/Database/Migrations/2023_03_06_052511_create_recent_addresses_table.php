<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateRecentAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recent_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->nullable();
            $table->foreignUuid('zone_id')->nullable();
            $table->text('pickup_coordinates')->nullable();
            $table->string('pickup_address')->nullable();
            $table->text('destination_coordinates')->nullable();
            $table->string('destination_address')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `recent_addresses` MODIFY `pickup_coordinates` POINT NULL');
            DB::statement('ALTER TABLE `recent_addresses` MODIFY `destination_coordinates` POINT NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recent_addresses');
    }
}
