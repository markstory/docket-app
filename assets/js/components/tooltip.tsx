import React from 'react';
import ReachTooltip, {TooltipProps} from '@reach/tooltip';

function Tooltip({children, ...props}: TooltipProps): JSX.Element {
  return (
    <ReachTooltip className="tooltip" {...props}>
      {children}
    </ReachTooltip>
  );
}

export default Tooltip;
