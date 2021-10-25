import {useState} from 'react';
import useKeyboardShortcut from './useKeyboardShortcut';

function useKeyboardListNav(itemCount: number): [number, React.Dispatch<React.SetStateAction<number>>] {
  const [focusedIndex, setFocusedIndex] = useState(-1);

  useKeyboardShortcut(['j'], () => {
    let newValue = focusedIndex + 1;
    if (newValue > itemCount) {
      newValue = 0;
    }
    setFocusedIndex(newValue);
  });
  useKeyboardShortcut(['k'], () => {
    let newValue = focusedIndex - 1;
    if (newValue < 0) {
      newValue = 0;
    }
    setFocusedIndex(newValue);
  });

  useKeyboardShortcut(['Escape'], () => {
    setFocusedIndex(-1);
  });

  return [focusedIndex, setFocusedIndex];
}

export default useKeyboardListNav;
