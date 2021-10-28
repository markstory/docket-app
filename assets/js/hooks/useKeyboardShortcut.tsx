import {useReducer, useEffect, useCallback} from 'react';

type ShortcutKeyMap = Record<string, boolean>;

type ShortcutCallback = (keymap: ShortcutKeyMap) => void;
type KeysAction =
  | {
      type: 'keydown' | 'keyup';
      key: string;
      ctrlKey: boolean;
      altKey: boolean;
    }
  | {
      type: 'reset';
      data: Record<string, boolean>;
    };

const ignoreTargets = ['INPUT', 'TEXTAREA'];

function keysReducer(state: ShortcutKeyMap, action: KeysAction): ShortcutKeyMap {
  switch (action.type) {
    case 'keydown':
      return {
        ...state,
        [action.key]: true,
        ctrlKey: action.ctrlKey,
        altKey: action.ctrlKey,
      };
    case 'keyup':
      return {
        ...state,
        [action.key]: false,
        ctrlKey: action.ctrlKey,
        altKey: action.altKey,
      };
    case 'reset':
      return {...action.data};
    default:
      return state;
  }
}

function useKeyboardShortcut(shortcutKeys: string[], callback: ShortcutCallback) {
  if (!shortcutKeys.length) {
    throw new Error('At least one shortcut key is required');
  }
  const initialMap = shortcutKeys.reduce<ShortcutKeyMap>(
    (currentKeys, key) => {
      currentKeys[key] = false;
      return currentKeys;
    },
    {
      ctrlKey: false,
      altKey: false,
    }
  );
  const [keys, setKeys] = useReducer(keysReducer, initialMap);

  const keydownListener = useCallback(
    (event: KeyboardEvent) => {
      const {key, target, repeat, ctrlKey, altKey, shiftKey} = event;
      if (repeat) {
        return;
      }
      if (!(target instanceof HTMLElement)) {
        return;
      }
      if (ignoreTargets.includes(target.tagName)) {
        return;
      }
      if (!shortcutKeys.includes(key)) {
        return;
      }

      event.preventDefault();
      setKeys({
        type: 'keydown',
        key: shiftKey ? key.toUpperCase() : key,
        ctrlKey,
        altKey,
      });
    },
    [shortcutKeys]
  );

  const keyupListener = useCallback(
    (event: KeyboardEvent) => {
      const {key, target, ctrlKey, altKey, shiftKey} = event;
      if (!(target instanceof HTMLElement)) {
        return;
      }
      if (ignoreTargets.includes(target.tagName)) {
        return;
      }
      if (!shortcutKeys.includes(key)) {
        return;
      }
      event.preventDefault();
      setKeys({
        type: 'keyup', 
        key: shiftKey ? key.toUpperCase() : key,
        ctrlKey,
        altKey,
      });
    },
    [shortcutKeys]
  );

  // Attach listeners
  useEffect(() => {
    window.addEventListener('keydown', keydownListener);
    return function cleanup() {
      window.removeEventListener('keydown', keydownListener);
    };
  }, []);

  useEffect(() => {
    window.addEventListener('keyup', keyupListener);
    return function cleanup() {
      window.removeEventListener('keyup', keyupListener);
    };
  }, []);

  // Fire the callback if all keys are active.
  useEffect(() => {
    let valid = true;
    // Compare the keys defined in the caller. This could include
    // ctrl/alt but might not.
    for (let i = 0; i < shortcutKeys.length; i++) {
      const key = shortcutKeys[i];
      if (keys[key] === false) {
        valid = false;
        break;
      }
    }
    if (!valid) {
      return;
    }
    // Ensure that we don't have extra ctrl/alt
    if (keys.ctrlKey !== initialMap.ctrlKey || keys.altKey !== initialMap.altKey) {
      return;
    }
    if (valid) {
      callback(keys);
      setKeys({type: 'reset', data: initialMap});
    }
  }, [keys]);
}

export default useKeyboardShortcut;
