import React from 'react';
import {Icon, InlineIcon, IconProps, addIcon} from '@iconify/react';

// Pull in the icons that are being used to help
// with treeshaking
import alert16 from '@iconify/icons-octicon/alert-16';
import archive16 from '@iconify/icons-octicon/archive-16';
import calendar16 from '@iconify/icons-octicon/calendar-16';
import check16 from '@iconify/icons-octicon/check-16';
import checkCircle16 from '@iconify/icons-octicon/check-circle-16';
import chevronDown16 from '@iconify/icons-octicon/chevron-down-16';
import circle16 from '@iconify/icons-octicon/circle-16';
import clippy16 from '@iconify/icons-octicon/clippy-16';
import dotfill16 from '@iconify/icons-octicon/dot-fill-16';
import grabber24 from '@iconify/icons-octicon/grabber-24';
import kebab16 from '@iconify/icons-octicon/kebab-horizontal-16';
import lock16 from '@iconify/icons-octicon/lock-16';
import pencil16 from '@iconify/icons-octicon/pencil-16';
import plus16 from '@iconify/icons-octicon/plus-16';
import pluscircle16 from '@iconify/icons-octicon/plus-circle-16';
import sun16 from '@iconify/icons-octicon/sun-16';
import trash16 from '@iconify/icons-octicon/trashcan-16';
import workflow16 from '@iconify/icons-octicon/workflow-16';

addIcon('alert', alert16);
addIcon('archive', archive16);
addIcon('calendar', calendar16);
addIcon('check', check16);
addIcon('checkcircle', checkCircle16);
addIcon('chevrondown', chevronDown16);
addIcon('circle', circle16);
addIcon('clippy', clippy16);
addIcon('dot', dotfill16);
addIcon('grabber', grabber24);
addIcon('lock', lock16);
addIcon('pencil', pencil16);
addIcon('plus', plus16);
addIcon('pluscircle', pluscircle16);
addIcon('kebab', kebab16);
addIcon('sun', sun16);
addIcon('trash', trash16);
addIcon('workflow', workflow16);

type WrappedProps = {width?: string} & IconProps;

function getWidth(size: string): number {
  switch (size) {
    case 'xsmall':
      return 12;
    case 'small':
      return 14;
    case 'normal':
      return 16;
    case 'medium':
      return 18;
    case 'large':
      return 20;
    case 'xlarge':
      return 24;
    default:
      return parseInt(size, 10);
  }
}

function WrappedIcon({width = 'normal', ...props}: WrappedProps) {
  return <Icon width={getWidth(width)} {...props} />;
}

function WrappedInlineIcon({width = 'normal', ...props}: WrappedProps) {
  return <InlineIcon width={getWidth(width)} {...props} />;
}

export {WrappedIcon as Icon, WrappedInlineIcon as InlineIcon};
