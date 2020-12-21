export const ONE_DAY_IN_MS = 1000 * 60 * 60 * 24;

const SHORT_DAYS = ['Sun', 'Mon', 'Tues', 'Wed', 'Thur', 'Fri', 'Sat'];
const LONG_DAYS = [
  'Sunday',
  'Monday',
  'Tuesday',
  'Wednesday',
  'Thursday',
  'Friday',
  'Saturday',
];

const SHORT_MONTHS = [
  'Jan',
  'Feb',
  'Mar',
  'Apr',
  'May',
  'Jun',
  'Jul',
  'Aug',
  'Sep',
  'Oct',
  'Nov',
  'Dec',
];

export function toDateString(date: Date): string {
  const day = date.getDate();
  return `${date.getFullYear()}-${date.getMonth() + 1}-${day < 10 ? '0' + day : day}`;
}

export function parseDate(input: string): Date {
  const date = new Date(input);
  date.setTime(date.getTime() + date.getTimezoneOffset() * 60 * 1000);

  return date;
}

export function now(): Date {
  const utcNow = new Date();
  utcNow.setTime(utcNow.getTime() - utcNow.getTimezoneOffset() * 60 * 1000);

  return utcNow;
}

export function formatCompactDate(date: Date | string): string {
  const input = date instanceof Date ? date : parseDate(date);
  const today = Math.floor(now().getTime() / ONE_DAY_IN_MS);
  const timestamp = Math.floor(input.getTime() / ONE_DAY_IN_MS);
  const delta = timestamp - today;
  let shortDate = `${SHORT_MONTHS[input.getMonth()]} ${input.getDate()}`;
  // In the past? Show the date.
  if (delta < 0) {
    return shortDate;
  }
  if (delta < 7) {
    return LONG_DAYS[input.getDay()];
  }
  if (delta < 1) {
    return 'Today';
  } else if (delta < 2) {
    return 'Tomorrow';
  }
  return shortDate;
}

export function formatDateHeading(date: Date | string): string {
  const input = date instanceof Date ? date : parseDate(date);

  const today = Math.floor(now().getTime() / ONE_DAY_IN_MS);
  const timestamp = Math.floor(input.getTime() / ONE_DAY_IN_MS);
  const delta = timestamp - today;
  let shortDate = `${SHORT_MONTHS[input.getMonth()]} ${input.getDate()}`;
  if (delta < 7) {
    shortDate = SHORT_DAYS[input.getDay()] + ' ' + shortDate;
  }
  if (delta < 1) {
    shortDate = 'Today ' + shortDate;
  } else if (delta < 2) {
    shortDate = 'Tomorrow ' + shortDate;
  }
  return shortDate;
}
