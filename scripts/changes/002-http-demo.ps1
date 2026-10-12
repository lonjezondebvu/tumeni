param([string]$BaseUrl='http://127.0.0.1:8000')
$ErrorActionPreference='Stop'
function Assert($condition,$message){if(!$condition){throw $message}}
function Session {
 $s=New-Object Microsoft.PowerShell.Commands.WebRequestSession
 $b=Invoke-RestMethod "$BaseUrl/api/v1/bootstrap" -WebSession $s
 return @{Session=$s;Token=$b.csrf_token}
}
function Post($s,$path,$body,$extra=@{}) {
 $headers=@{'X-CSRF-TOKEN'=$s.Token;Accept='application/json'}
 foreach($k in $extra.Keys){$headers[$k]=$extra[$k]}
 return Invoke-RestMethod "$BaseUrl/api/v1/$path" -Method Post -WebSession $s.Session -Headers $headers -ContentType 'application/json' -Body ($body|ConvertTo-Json -Depth 10 -Compress)
}
function Get($s,$path){Invoke-RestMethod "$BaseUrl/api/v1/$path" -WebSession $s.Session -Headers @{Accept='application/json'}}
function Login($s,$role){Post $s 'dev/login' @{role=$role}|Out-Null;$s.Token=(Get $s 'bootstrap').csrf_token}
$admin=Session
Assert ((Get $admin 'bootstrap').development) 'This script only supports fictional development data'
Login $admin 'admin'
$driver=Session;Login $driver 'driver'
$application=Get $driver 'driver/application'
if($application.profile.full_name -and $application.profile.full_name -ne 'HTTP DEMO Fictional Driver'){throw 'Refusing to change a non-demo driver application'}
if($application.onboarding_state -ne 'approved'){
 $expiry=(Get-Date).AddYears(1).ToString('yyyy-MM-dd')
 if($application.onboarding_state -ne 'submitted'){
  $profile=@{full_name='HTTP DEMO Fictional Driver';date_of_birth='1990-01-01';nationality='Malawian';address='HTTP DEMO only';district='Mzuzu';emergency_name='HTTP DEMO contact';emergency_phone='+265991234567';national_id_number='HTTP-DEMO-ID-1';national_id_expires_at=$expiry;licence_number='HTTP-DEMO-LICENCE';licence_class='B';licence_expires_at=$expiry;insurance_expires_at=$expiry;fitness_expires_at=$expiry;permit_expires_at=$expiry;make='Toyota';model='Corolla';colour='Silver';plate='DEMO 1234';vin='HTTP-DEMO-VIN';year=2020;seats=5;ownership='owned';consent=$true}
  $application=Post $driver 'driver/application' $profile
  Add-Type -AssemblyName System.Net.Http
  $handler=[System.Net.Http.HttpClientHandler]::new();$handler.CookieContainer=$driver.Session.Cookies
  $client=[System.Net.Http.HttpClient]::new($handler)
  $client.DefaultRequestHeaders.Add('X-CSRF-TOKEN',$driver.Token);$client.DefaultRequestHeaders.Add('Accept','application/json')
  try{
   $png=[Convert]::FromBase64String('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j6N8AAAAASUVORK5CYII=')
   foreach($kind in $application.required_documents){
    $multipart=[System.Net.Http.MultipartFormDataContent]::new()
    try{
     $multipart.Add([System.Net.Http.StringContent]::new($kind),'kind')
     $file=[System.Net.Http.ByteArrayContent]::new($png);$file.Headers.ContentType=[System.Net.Http.Headers.MediaTypeHeaderValue]::new('image/png')
     $multipart.Add($file,'document','FICTIONAL-DEMO.png')
     $response=$client.PostAsync("$BaseUrl/api/v1/driver/document",$multipart).GetAwaiter().GetResult()
     try{[void]$response.EnsureSuccessStatusCode()}finally{$response.Dispose()}
    }finally{$multipart.Dispose()}
   }
  }finally{$client.Dispose();$handler.Dispose()}
  $application=Post $driver 'driver/application/submit' @{}
 }
 foreach($document in $application.documents){Post $admin "admin/drivers/1/documents/$($document.id)/review" @{status='accepted';reason='HTTP DEMO ONLY: fictional document reviewed, not real verification'}|Out-Null}
 $checks=@{};foreach($check in $application.verification_checks){$checks[$check]=$true}
 Post $admin 'admin/drivers/1/review' @{status='approved';vehicle_approved=$true;reason='HTTP DEMO ONLY: fictional driver and vehicle verification';checks=$checks}|Out-Null
}
Post $driver 'driver/online' @{online=$true}|Out-Null
$before=(Get $driver 'driver').available_credit_minor
$rider=Session
$phone='+26599'+(Get-Random -Minimum 1000000 -Maximum 9999999)
$otp=Post $rider 'auth/otp' @{phone=$phone}
Post $rider 'auth/verify' @{challenge_id=$otp.challenge_id;code=$otp.development_code;first_name='Demo rider'}|Out-Null
$rider.Token=(Get $rider 'bootstrap').csrf_token
$q=Post $rider 'quotes' @{pickup=@{landmark_id=1};destination=@{landmark_id=2}}
$key=[guid]::NewGuid().ToString()
$body=@{quote_id=$q.id;payment_method='cash';pickup_note='Main gate, demonstration only'}
$t=Post $rider 'trips' $body @{'Idempotency-Key'=$key}
$retry=Post $rider 'trips' $body @{'Idempotency-Key'=$key}
Assert ($t.id -eq $retry.id) 'Duplicate booking created a trip'
Post $driver "driver/offers/$($t.id)/accept" @{}|Out-Null
Assert (((Get $driver 'driver').available_credit_minor) -eq ($before-$q.pricing.fee_minor)) 'Reservation mismatch'
Post $driver "trips/$($t.id)/arrive" @{}|Out-Null
Post $driver "trips/$($t.id)/start" @{pin=$t.pickup_pin}|Out-Null
Post $driver "trips/$($t.id)/complete" @{}|Out-Null
Post $driver "trips/$($t.id)/complete" @{}|Out-Null
$receipt=Get $rider "trips/$($t.id)"
Assert ($receipt.state -eq 'completed') 'Trip not completed'
Assert ($receipt.fee_state -eq 'captured') 'Fee not captured'
Assert (((Get $driver 'driver').available_credit_minor) -eq ($before-$q.pricing.fee_minor)) 'Fee charged twice'
$history=Get $admin 'admin'
Assert (@($history.events|Where-Object {$_.trip_id -eq $t.id}).Count -eq 5) 'Trip event history mismatch'
Assert (@($history.ledger|Where-Object {$_.trip_id -eq $t.id -and $_.kind -eq 'capture'}).Count -eq 1) 'Capture history mismatch'
$q2=Post $rider 'quotes' @{pickup=@{landmark_id=1};destination=@{landmark_id=2}}
$t2=Post $rider 'trips' @{quote_id=$q2.id;payment_method='cash'} @{'Idempotency-Key'=[guid]::NewGuid().ToString()}
Post $driver "driver/offers/$($t2.id)/accept" @{}|Out-Null
Post $rider "trips/$($t2.id)/cancel" @{reason='HTTP demo: genuine pre-pickup cancellation'}|Out-Null
Assert (((Get $driver 'driver').available_credit_minor) -eq ($before-$q.pricing.fee_minor)) 'Cancelled fee not released'
Post $driver 'driver/online' @{online=$false}|Out-Null
Write-Output 'PASS: HTTP OTP, approval, online, quote, booking retry, accept, reserve, arrival, PIN, complete retry, receipt, admin history, cancellation release.'
Write-Output "Completed trip: $($t.id); cancelled trip: $($t2.id). Fictional demonstration data retained."