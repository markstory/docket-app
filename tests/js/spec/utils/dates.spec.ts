import {addDays, addMonths, format} from 'date-fns';
import {
  formatDateHeading,
  formatCompactDate,
  getToday,
  parseDate,
  parseDateInput,
  toDateString,
} from 'app/utils/dates';

describe('utils/dates', function() {
  const today = getToday();
  const tomorrow = addDays(today, 1);

  describe('parseDate()', function() {
    it('handles valid dates', function() {
      expect(parseDate('2020-12-20')).toBeInstanceOf(Date);
    });

    it('handles invalid dates', function() {
      const result = parseDate('not a date');
      expect(result).toBeInstanceOf(Date);
      expect(result.valueOf()).toBeNaN();
    });
  });

  describe('parseDateInput()', function() {
    it('handles today', function() {
      for (let option of ['today', 'Today', 'TODAY']) {
        const result = parseDateInput(option);
        assertDefined(result);
        expect(result).toBeInstanceOf(Date);
        expect(toDateString(result)).toEqual(toDateString(new Date()));
      }
    });

    it('handles tomorrow', function() {
      for (let option of ['tomorrow', 'Tomorrow', 'TOMORrow']) {
        const result = parseDateInput(option);
        assertDefined(result);
        expect(result).toBeInstanceOf(Date);
        expect(toDateString(result)).toEqual(toDateString(tomorrow));
      }
    });

    it('handles weekdays', function() {
      let result = parseDateInput('Wednesday');
      assertDefined(result);
      expect(result.getDay()).toEqual(3);
      expect(result.getTime()).toBeGreaterThanOrEqual(today.getTime());

      result = parseDateInput('sunday');
      assertDefined(result);
      expect(result.getDay()).toEqual(0);
      expect(result.getTime()).toBeGreaterThanOrEqual(today.getTime());
    });

    it('handles wrapping days in the past to the next year', function() {
      const inPast = addMonths(today, -2);
      const pastMonth = format(inPast, 'MMM');
      const result = parseDateInput(`${pastMonth} 10`);
      assertDefined(result);
      expect(result.getDate()).toEqual(10);
      expect(result.getFullYear()).toBeGreaterThan(inPast.getFullYear());
    });
  });

  describe('formatCompactDate()', function() {
    it('accepts Date objects', function() {
      expect(formatCompactDate(today)).toEqual('Today');
      expect(formatCompactDate(tomorrow)).toEqual('Tomorrow');
    });

    it('formats weekdays', function() {
      const day = addDays(new Date(), 3);
      expect(formatCompactDate(day)).toMatch(/^.*day$/);
    });

    it('accepts strings', function() {
      expect(formatCompactDate('2019-12-20')).toEqual('Dec 20 2019');
    });
  });

  describe('formatDateHeading()', function() {
    it('splits heading up for today', function() {
      const result = formatDateHeading(today);
      expect(result[0]).toEqual('Today');
      expect(result[1]).toMatch(/\w+ \d{1,2}$/);
    });

    it('splits heading up for tomorrow', function() {
      const result = formatDateHeading(tomorrow);
      expect(result[0]).toEqual('Tomorrow');
      expect(result[1]).toMatch(/\w+ \d{1,2}$/);
    });

    it('splits heading up for weekdays', function() {
      const weekday = addDays(today, 3);
      const result = formatDateHeading(weekday);
      expect(result[0]).not.toEqual('Today');
      expect(result[0]).toMatch(/^\w+day$/);
      expect(result[1]).toMatch(/^\w+ \d{1,2}$/);
    });

    it('formats date values', function() {
      const weekday = addDays(today, 10);
      const result = formatDateHeading(weekday);
      expect(result[0]).toMatch(/^\w+ \d{1,2}$/);
      expect(result[1]).toEqual('');
    });
  });
});
