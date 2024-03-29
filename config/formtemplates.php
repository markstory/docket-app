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
                <span class="select-box-value"></span>
                <input type="text" class="select-box-input" />
            </select-box-current>
            <select-box-menu>{{options}}</select-box-menu>
        </select-box>
    HTML,
    'due-on' => <<<HTML
        <due-on {{attrs}}>
            {{hidden}}
            <input type="hidden" name="evening" value="0" />
            <drop-down clonemenu="false" portalscope="local">
                <button type="button"
                    class="button button-secondary"
                    aria-haspopup="true"
                    aria-controls="dueon-{{id}}"
                    data-dueon-display
                >
                    {{label}}
                </button>
                <drop-down-menu id="dueon-{{id}}" role="menu">
                    {{options}}
                </drop-down-menu>
            </drop-down>
            <label class="due-on-evening" for="task-evening-{{id}}" role="button" tabindex="0">
                {{inputEvening}}
                <span class="toggle-evening icon-evening" title="Set task to evening">{{iconEvening}}</span>
                <span class="toggle-day icon-tomorrow" title="Set task to daytime">{{iconDay}}</span>
            </label>
        </due-on>
    HTML,
];
