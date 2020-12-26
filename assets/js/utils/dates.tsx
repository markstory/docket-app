import {differenceInDays, format, parse} from 'date-fns';

export const ONE_DAY_IN_MS = 60 * 60 * 24 * 1000;

export function toDateString(date: Date): string {
  return format(date, 'yyyy-MM-dd');
}

export function parseDate(input: string): Date {
  return parse(input, 'yyyy-MM-dd', new Date());
}

export function formatCompactDate(date: Date | string): string {
  const input = date instanceof Date ? date : parseDate(date);
  const delta = differenceInDays(new Date(), input);

  // In the past? Show the date.
  if (delta < 0) {
    return format(input, 'MMM d');
  }
  if (delta < 1) {
    return 'Today';
  } else if (delta < 2) {
    return 'Tomorrow';
  }
  if (delta < 7) {
    return format(input, 'iiii');
  }
  return format(input, 'MMM d');
}

export function formatDateHeading(date: Date | string): string {
  const input = date instanceof Date ? date : parseDate(date);
  const delta = differenceInDays(input, new Date());

  let shortDate = format(input, delta < 7 ? 'EEEE MMM d' : 'MMM d');
  if (delta < 1) {
    shortDate = 'Today ' + shortDate;
  } else if (delta < 2) {
    shortDate = 'Tomorrow ' + shortDate;
  }
  return shortDate;
}
