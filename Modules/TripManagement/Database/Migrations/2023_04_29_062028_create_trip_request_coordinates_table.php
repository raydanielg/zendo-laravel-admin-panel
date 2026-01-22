<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateTripRequestCoordinatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trip_request_coordinates', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('trip_request_id');
            $table->text('pickup_coordinates')->nullable();
            $table->string('pickup_address')->nullable();
            $table->text('destination_coordinates')->nullable();
            $table->boolean('is_reached_destination')->default(false);
            $table->string('destination_address')->nullable();
            $table->text('intermediate_coordinates')->nullable();
            $table->text('int_coordinate_1')->nullable();
            $table->boolean('is_reached_1')->default(false);
            $table->text('int_coordinate_2')->nullable();
            $table->boolean('is_reached_2')->default(false);
            $table->text('intermediate_addresses')->nullable();
            $table->text('start_coordinates')->nullable();
            $table->text('drop_coordinates')->nullable();
            $table->text('driver_accept_coordinates')->nullable();
            $table->text('customer_request_coordinates')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `trip_request_coordinates` MODIFY `pickup_coordinates` POINT NULL');
            DB::statement('ALTER TABLE `trip_request_coordinates` MODIFY `destination_coordinates` POINT NULL');
            DB::statement('ALTER TABLE `trip_request_coordinates` MODIFY `int_coordinate_1` POINT NULL');
            DB::statement('ALTER TABLE `trip_request_coordinates` MODIFY `int_coordinate_2` POINT NULL');
            DB::statement('ALTER TABLE `trip_request_coordinates` MODIFY `start_coordinates` POINT NULL');
            DB::statement('ALTER TABLE `trip_request_coordinates` MODIFY `drop_coordinates` POINT NULL');
            DB::statement('ALTER TABLE `trip_request_coordinates` MODIFY `driver_accept_coordinates` POINT NULL');
            DB::statement('ALTER TABLE `trip_request_coordinates` MODIFY `customer_request_coordinates` POINT NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trip_request_coordinates');
    }
}
