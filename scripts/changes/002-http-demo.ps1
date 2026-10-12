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
$admin=Session; Login $admin 'admin'
Post $admin 'admin/drivers/1/review' @{status='approved';vehicle_approved=$true;reason='HTTP demo: fictional fixture documents reviewed'}|Out-Null
$driver=Session;Login $driver 'driver'
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