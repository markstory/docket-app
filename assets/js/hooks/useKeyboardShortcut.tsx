import {useReducer, useEffect, useCallback} from 'react';

type ShortcutKeyMap = Record<string, boolean>;

type ShortcutCallback = (keymap: ShortcutKeyMap) => void;
type KeysAction =
  | {
      type: 'keydown' | 'keyup';
      key: string;
    }
  | {
      type: 'reset';
      data: Record<string, string>;
    };

const ignoreTargets = ['INPUT', 'TEXTAREA'];

function keysReducer(state: ShortcutKeyMap, action: KeysAction) {
  switch (action.type) {
    case 'keydown':
      return {...state, [action.key]: true};
    case 'keyup':
      return {...state, [action.key]: false};
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
  const initialMap = shortcutKeys.reduce<ShortcutKeyMap>((currentKeys, key) => {
    currentKeys[key] = false;
    return currentKeys;
  }, {});
  // TODO figure out typing here.
  const [keys, setKeys] = useReducer(keysReducer, initialMap);

  const keydownListener = useCallback(
    (event: KeyboardEvent) => {
      const {key, target, repeat} = event;
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
      setKeys({type: 'keydown', key});
    },
    [shortcutKeys]
  );

  const keyupListener = useCallback(
    (event: KeyboardEvent) => {
      const {key, target} = event;
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
      setKeys({type: 'keyup', key});
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
    const allActive = Object.values(keys).filter(value => value === false).length === 0;
    if (allActive) {
      callback(keys);
      setKeys({type: 'reset', data: initialMap});
    }
  }, [keys]);
}

export default useKeyboardShortcut;
