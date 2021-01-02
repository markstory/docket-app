Hi <?= $name ?>,

Someone (hopefully you) has requested a password reset for TODO.
If this was you, follow the URL below to complete the process.
If this wasn't you, don't worry you can ignore this email.

<?= $this->Url->build(['_name' => 'users:newPassword', 'token' => $token], ['fullBase' => true]) ?>
