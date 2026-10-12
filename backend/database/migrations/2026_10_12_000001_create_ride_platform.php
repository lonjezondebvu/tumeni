<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  Schema::create('accounts',function(Blueprint $t){$t->id();$t->string('phone')->unique();$t->string('first_name');$t->string('role')->default('rider');$t->timestamps();});
  Schema::create('otp_challenges',function(Blueprint $t){$t->uuid('id')->primary();$t->string('phone');$t->string('code_hash');$t->unsignedInteger('attempts')->default(0);$t->timestampTz('expires_at');$t->timestampTz('consumed_at')->nullable();});
  Schema::create('service_settings',function(Blueprint $t){$t->id();$t->string('name');$t->jsonb('bounds');$t->string('opens')->default('00:00');$t->string('closes')->default('23:59');$t->string('timezone')->default('Africa/Blantyre');$t->boolean('enabled')->default(true);$t->timestamps();});
  Schema::create('pricing_rules',function(Blueprint $t){$t->id();$t->string('currency')->default('MWK');$t->bigInteger('base_minor');$t->bigInteger('per_km_minor');$t->unsignedInteger('commission_bps');$t->timestamps();});
  Schema::create('landmarks',function(Blueprint $t){$t->id();$t->string('name');$t->jsonb('aliases');$t->decimal('latitude',10,7);$t->decimal('longitude',10,7);$t->boolean('verified')->default(false);$t->string('meeting_point')->nullable();$t->timestamps();});
  Schema::create('drivers',function(Blueprint $t){$t->id();$t->foreignId('account_id')->unique()->constrained('accounts');$t->string('status')->default('pending');$t->boolean('online')->default(false);$t->string('vehicle');$t->string('plate');$t->boolean('vehicle_approved')->default(false);$t->string('document_path')->nullable();$t->jsonb('application')->nullable();$t->timestamps();});
  Schema::create('quotes',function(Blueprint $t){$t->uuid('id')->primary();$t->jsonb('pickup');$t->jsonb('destination');$t->jsonb('pricing');$t->unsignedInteger('distance_m');$t->unsignedInteger('duration_s');$t->string('provider');$t->timestampTz('expires_at');$t->timestamps();});
  Schema::create('trips',function(Blueprint $t){$t->uuid('id')->primary();$t->foreignId('rider_id')->constrained('accounts');$t->foreignId('driver_id')->nullable()->constrained('drivers');$t->uuid('quote_id')->unique();$t->foreign('quote_id')->references('id')->on('quotes');$t->string('request_key');$t->string('request_hash');$t->string('state');$t->string('payment_method');$t->string('payment_status')->default('unpaid');$t->string('fee_state')->default('none');$t->jsonb('pickup');$t->jsonb('destination');$t->text('pickup_note')->nullable();$t->jsonb('pricing');$t->string('pin_hash');$t->text('pin_encrypted');$t->unsignedInteger('pin_attempts')->default(0);$t->timestampTz('pin_locked_until')->nullable();$t->timestampTz('expires_at');$t->timestampTz('completed_at')->nullable();$t->timestamps();$t->unique(['rider_id','request_key']);});
  Schema::create('fee_reservations',function(Blueprint $t){$t->id();$t->uuid('trip_id')->unique();$t->foreign('trip_id')->references('id')->on('trips');$t->foreignId('driver_id')->constrained('drivers');$t->bigInteger('amount_minor');$t->string('state');$t->timestamps();});
  Schema::create('credit_ledger',function(Blueprint $t){$t->id();$t->foreignId('driver_id')->constrained('drivers');$t->uuid('trip_id')->nullable();$t->string('reference')->unique();$t->string('kind');$t->string('currency')->default('MWK');$t->bigInteger('amount_minor');$t->text('reason');$t->foreignId('actor_id')->nullable()->constrained('accounts');$t->timestampTz('created_at')->useCurrent();});
  Schema::create('commissions',function(Blueprint $t){$t->id();$t->uuid('trip_id')->unique();$t->bigInteger('amount_minor');$t->string('currency')->default('MWK');$t->string('source');$t->timestampTz('created_at')->useCurrent();});
  Schema::create('settlements',function(Blueprint $t){$t->id();$t->uuid('trip_id')->unique();$t->bigInteger('driver_minor');$t->string('state');$t->timestampTz('created_at')->useCurrent();});
  Schema::create('trip_events',function(Blueprint $t){$t->id();$t->uuid('trip_id');$t->foreignId('actor_id')->nullable()->constrained('accounts');$t->string('event');$t->jsonb('details');$t->timestampTz('created_at')->useCurrent();});
  Schema::create('audit_records',function(Blueprint $t){$t->id();$t->foreignId('actor_id')->constrained('accounts');$t->string('action');$t->string('resource');$t->text('reason');$t->jsonb('details');$t->timestampTz('created_at')->useCurrent();});
  Schema::create('incidents',function(Blueprint $t){$t->id();$t->foreignId('account_id')->constrained('accounts');$t->uuid('trip_id')->nullable();$t->string('category');$t->text('description');$t->string('status')->default('open');$t->timestampTz('review_due_at');$t->timestamps();});
  Schema::create('offer_declines',function(Blueprint $t){$t->id();$t->foreignId('driver_id')->constrained('drivers');$t->uuid('trip_id');$t->unique(['driver_id','trip_id']);});
  DB::statement("CREATE UNIQUE INDEX one_active_driver_trip ON trips(driver_id) WHERE state IN ('assigned','driver_arriving','driver_arrived','in_progress')");
  DB::statement("CREATE UNIQUE INDEX one_active_rider_trip ON trips(rider_id) WHERE state IN ('matching','assigned','driver_arriving','driver_arrived','in_progress')");
  DB::statement('ALTER TABLE pricing_rules ADD CONSTRAINT valid_pricing CHECK (base_minor >= 0 AND per_km_minor >= 0 AND commission_bps BETWEEN 0 AND 10000)');
  DB::unprepared("CREATE OR REPLACE FUNCTION immutable_record() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'Auditable records are append-only'; END; $$");
  foreach(['credit_ledger','commissions','trip_events','audit_records','pricing_rules'] as $table) DB::statement("CREATE TRIGGER immutable_{$table} BEFORE UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION immutable_record()");
 }
 public function down(): void {
  foreach(['offer_declines','incidents','audit_records','trip_events','settlements','commissions','credit_ledger','fee_reservations','trips','quotes','drivers','landmarks','pricing_rules','service_settings','otp_challenges','accounts'] as $table) Schema::dropIfExists($table);
  DB::statement('DROP FUNCTION IF EXISTS immutable_record()');
 }
};