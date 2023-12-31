import htmx from 'htmx.org';

type Keybinding = {
  shiftKey: boolean;
  ctrlKey: boolean;
  altKey: boolean;
  metaKey: boolean;
  key: string;
};

type HotkeyAction = (keybinding: Keybinding) => void;

type Hotkey = {
  binding: Keybinding;
  action: HotkeyAction;
};

(function () {
  const hotkeys: Hotkey[] = [];

  function parseKeybinding(binding: string): Keybinding {
    const parsed: Keybinding = {
      shiftKey: false,
      ctrlKey: false,
      altKey: false,
      metaKey: false,
      key: '',
    };
    const keys = binding.toLowerCase().split('+');
    for (var key of keys) {
      switch (key) {
        case 'shift':
          parsed.shiftKey = true;
          break;
        case 'ctrl':
          parsed.ctrlKey = true;
          break;
        case 'alt':
          parsed.altKey = true;
          break;
        case 'meta':
          parsed.metaKey = true;
          break;
        default:
          if (key.length > 1) {
            throw new Error('Keybindings can only be a single letter');
          }
          parsed.key = key;
      }
    }
    return parsed;
  }

  function processHotkeys(element: HTMLElement) {
    if (!element.querySelectorAll) {
      return;
    }
    for (const elem of element.querySelectorAll('[data-hotkey]')) {
      const hotkey = String(elem.getAttribute('data-hotkey'));
      // TODO add more actions.
      const action = 'click';
      if (elem instanceof HTMLElement && action === 'click') {
        const keybinding = parseKeybinding(hotkey);
        hotkeys.push({binding: keybinding, action: () => elem.click()});
      }
    }
  }

  function addListener() {
    window.addEventListener('keydown', function (event) {
      for (var hotkey of hotkeys) {
        if (matchHotkey(hotkey, event)) {
          console.debug('matched hotkey', hotkey);
          hotkey.action(hotkey.binding);
        }
      }
    });
  }

  function matchHotkey(hotkey: Hotkey, event: KeyboardEvent): boolean {
    var key = event.key.toLowerCase();
    if (key !== hotkey.binding.key) {
      return false;
    }
    for (let prop in hotkey.binding) {
      // @ts-ignore-next-line I can't be bothered to convince typescript this is ok.
      if (prop !== 'key' && event[prop] !== hotkey.binding[prop]) {
        return false;
      }
    }
    return true;
  }

  htmx.defineExtension('hotkeys', {
    onEvent: function (name, evt) {
      if (name !== 'htmx:afterProcessNode') {
        return;
      }
      const element = evt.detail.elt;
      // Only process nodes that have the extension set.
      if (element.getAttribute && element.getAttribute('hx-ext') !== 'hotkeys') {
        return;
      }
      processHotkeys(element);
      addListener();
    },
  });
})();
