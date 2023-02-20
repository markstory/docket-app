import {isValidElement} from 'react';
import ReachTooltip, {TooltipProps} from '@reach/tooltip';

function Tooltip({children, ...props}: TooltipProps): JSX.Element | null {
  if (!props.label) {
    return isValidElement(children) ? children : null;
  }
  return (
    <ReachTooltip className="tooltip" {...props}>
      {children}
    </ReachTooltip>
  );
}

export default Tooltip;
