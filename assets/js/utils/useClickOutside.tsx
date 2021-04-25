import {useEffect} from 'react';

type Handler = (event: MouseEvent | TouchEvent) => void;

function useOnClickOutside<T extends HTMLElement>(
  ref: React.RefObject<T>,
  handler: Handler
) {
  useEffect(
    () => {
      const listener: Handler = event => {
        // Do nothing if the ref is empty
        if (!ref.current) {
          return;
        }
        // Do nothing if the current element is in the ref.
        const target = event.target as HTMLElement;
        if (!target || !ref.current.contains(target)) {
          return;
        }
        handler(event);
      };
      document.addEventListener('mousedown', listener);
      document.addEventListener('touchstart', listener);

      return () => {
        document.removeEventListener('mousedown', listener);
        document.removeEventListener('touchstart', listener);
      };
    },
    // Change on each ref/handler change. Because handler will
    // likely mutate on each render this will always trigger.
    [ref, handler]
  );
}
export default useOnClickOutside;
