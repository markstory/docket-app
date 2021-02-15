import React from 'react';
import ReachTooltip, {TooltipProps} from '@reach/tooltip';

function Tooltip({children, ...props}: TooltipProps): JSX.Element | null {
  if (!props.label) {
    return React.isValidElement(children) ? children : null;
  }
  return (
    <ReachTooltip className="tooltip" {...props}>
      {children}
    </ReachTooltip>
  );
}

export default Tooltip;
