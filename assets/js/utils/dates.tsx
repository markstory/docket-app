import {differenceInDays, addDays, format, parse} from 'date-fns';
import {t} from 'app/locale';

export const ONE_DAY_IN_MS = 60 * 60 * 24 * 1000;

export function toDateString(date: Date): string {
  return format(date, 'yyyy-MM-dd');
}

export function parseDate(input: string | Date): Date {
  if (input instanceof Date) {
    return input;
  }
  return parse(input, 'yyyy-MM-dd', new Date());
}

export function parseDateInput(input: string): Date | undefined {
  const today = getToday();
  if (input.toLowerCase() === 'today') {
    return today;
  }
  if (input.toLowerCase() === 'tomorrow') {
    return addDays(today, 1);
  }
  const formats = ['MMM d', 'MMM dd', 'MMMM d', 'EEEE', 'yyyy-MM-dd'];
  for (let i = 0; i < formats.length; i++) {
    try {
      let result = parse(input, formats[i], today);
      if (isNaN(result.valueOf())) {
        continue;
      }
      // Day is in the past. Move to the next period
      // to ensure tasks are created in the future.
      if (result.getTime() < today.getTime()) {
        if (formats[i] === 'EEEE') {
          // Was a weekday go to next week.
          result = addDays(result, 7);
        } else {
          // Was a month day expression, move to next year.
          result.setFullYear(result.getFullYear() + 1);
        }
      }
      return result;
    } catch (e) {
      // Do nothing with invalid data;
    }
  }
  return undefined;
}

export function getToday() {
  const today = new Date();
  today.setHours(0);
  today.setMinutes(0, 0, 0);
  return today;
}

export function getDiff(date: Date | string, compare?: Date) {
  compare = compare || getToday();
  const input = parseDate(date);
  return differenceInDays(input, compare);
}

export function formatCompactDate(date: Date | string): string {
  const input = parseDate(date);
  const delta = differenceInDays(input, getToday());

  // In the past? Show the date.
  if (delta < -90) {
    return format(input, 'MMM d yyyy');
  }
  if (delta < 0) {
    return format(input, 'MMM d');
  }
  if (delta < 1) {
    return t('Today');
  } else if (delta < 2) {
    return t('Tomorrow');
  }
  if (delta < 7) {
    return format(input, 'iiii');
  }
  return format(input, 'MMM d');
}

export function formatDateHeading(
  date: Date | string
): [heading: string, subheading: string] {
  const input = parseDate(date);
  if (isNaN(input.valueOf())) {
    return ['', ''];
  }
  const delta = differenceInDays(input, getToday());

  const shortDate = format(input, 'MMM d');
  if (delta < 1) {
    return [t('Today'), shortDate];
  } else if (delta < 2) {
    return [t('Tomorrow'), shortDate];
  } else if (delta < 7) {
    return [format(input, 'EEEE'), shortDate];
  }
  return [shortDate, ''];
}
