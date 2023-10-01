<?php
declare(strict_types=1);

return [
    'inputContainer' => '<div class="form-control {{required}}">{{content}}</div>',
    'inputContainerError' => '<div class="form-control is-error {{required}}">{{content}}</div>',
    'formGroup' => '{{label}}<div class="form-input">{{input}}{{error}}</div>',
    'label' => '<div class="form-label-group"><label{{attrs}}>{{text}}</label>{{help}}</div>',
];
