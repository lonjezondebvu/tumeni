<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('drivers',function(Blueprint $t){$t->string('onboarding_state')->default('draft');$t->text('profile_encrypted')->nullable();$t->string('identity_hash')->nullable()->unique();$t->timestampTz('submitted_at')->nullable();$t->timestampTz('verified_at')->nullable();$t->timestampTz('trial_granted_at')->nullable();$t->bigInteger('trial_credit_minor')->default(0);$t->text('review_reason')->nullable();});
  Schema::create('driver_documents',function(Blueprint $t){$t->id();$t->foreignId('driver_id')->constrained('drivers');$t->string('kind');$t->string('path');$t->string('mime');$t->string('status')->default('pending');$t->text('review_reason')->nullable();$t->foreignId('reviewed_by')->nullable()->constrained('accounts');$t->timestampTz('reviewed_at')->nullable();$t->timestamps();$t->unique(['driver_id','kind']);});
  Schema::create('driver_topups',function(Blueprint $t){$t->uuid('id')->primary();$t->foreignId('driver_id')->constrained('drivers');$t->string('request_key');$t->string('tx_ref')->unique();$t->string('provider_reference')->nullable()->unique();$t->bigInteger('amount_minor');$t->string('currency')->default('MWK');$t->string('mode');$t->string('status')->default('pending');$t->text('checkout_url')->nullable();$t->timestampTz('credited_at')->nullable();$t->timestamps();$t->unique(['driver_id','request_key']);});
 }
 public function down():void{Schema::dropIfExists('driver_topups');Schema::dropIfExists('driver_documents');Schema::table('drivers',function(Blueprint $t){$t->dropUnique(['identity_hash']);$t->dropColumn(['onboarding_state','profile_encrypted','identity_hash','submitted_at','verified_at','trial_granted_at','trial_credit_minor','review_reason']);});}
};
