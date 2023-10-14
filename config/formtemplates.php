<?php
declare(strict_types=1);

return [
    'checkboxFormGroup' => '{{label}}<div class="form-input">{{input}}{{error}}</div>',
    'inputContainer' => '<div class="form-control {{required}}">{{content}}</div>',
    'inputContainerError' => '<div class="form-control is-error {{required}}">{{content}}</div>',
    'formGroup' => '{{label}}<div class="form-input">{{input}}{{error}}</div>',
    'label' => '<div class="form-label-group"><label{{attrs}}>{{text}}</label>{{help}}</div>',
    'error' => '<div class="form-error" id="{{id}}">{{content}}</div>',
    // Webcomponent for custom select boxes
    'select-box-option' => '<select-box-option value="{{value}}"{{attrs}}>{{text}}</select-box-option>',
    'select-box' => <<<HTML
    <select-box name="{{name}}"{{attrs}}>
        {{hidden}}
        <select-box-current>
            <input type="text" class="select-box-input" />
        </select-box-current>
        <select-box-menu>{{options}}</select-box-menu>
    </select-box>
    HTML,
];
