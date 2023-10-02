<?php
declare(strict_types=1);
/**
 * Render the profile menu in the sidebar
 */

$avatarUrl = "https://www.gravatar.com/avatar/{$identity->avatar_hash}?s=50&default=retro";
?>
<div class="profile-menu">
    <button
        class="avatar" 
        aria-haspopup="true" 
        aria-controls="profile-menu"
        type="button"
        hx-get="<?= $this->Url->build(['_path' => 'Users::profileMenu']) ?>"
        hx-target="#profile-menu"
    >
        <?= $this->Html->image($avatarUrl, ['height' => 50, 'width' => 50]) ?>
    </button>
    <div id="profile-menu" role="menu" data-reach-menu-list="">
    </div>
</div>
