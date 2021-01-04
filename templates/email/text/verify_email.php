Hi <?= $name ?>,

<?php if ($firstTime): ?>
To keep your help ensure you're a human we need to verify that <?= $unverifiedEmail ?> belongs to a human. Failing to verify your account will result in it being deleted in 10 days.
<?php else: ?>
Before your email can be changed from <?= $email ?> to <?= $unverifiedEmail ?> we need you to verify that <?= $unverifiedEmail ?> is yours.
<?php endif; ?>

Make sure you're logged in and then visit the following link:

<?= $this->Url->build(['_name' => 'users:verifyEmail', 'token' => $token], ['fullBase' => true]) ?>
