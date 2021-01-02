Hi <?= $name ?>,

Before your email can be changed from <?= $email ?> to <?= $unverified_email ?>
we need you to verify that <?= $unverified_email ?> is yours.

Make sure you're logged in and then visit the following link:

<?= $this->Url->build(['_name' => 'users:verifyEmail', 'token' => $token], ['fullBase' => true]) ?>
