<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {
  Schema::create('payment_events',function(Blueprint $t){$t->id();$t->string('event_id')->unique();$t->uuid('trip_id');$t->unsignedInteger('sequence');$t->string('status');$t->bigInteger('amount_minor');$t->string('currency');$t->string('payload_hash');$t->boolean('applied');$t->timestampTz('created_at')->useCurrent();});
  DB::statement('CREATE TRIGGER immutable_payment_events BEFORE UPDATE OR DELETE ON payment_events FOR EACH ROW EXECUTE FUNCTION immutable_record()');
 }
 public function down():void {Schema::dropIfExists('payment_events');}
};