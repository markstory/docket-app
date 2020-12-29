import {addDays, addMonths, format} from 'date-fns';
import {parseDate, parseDateInput, toDateString} from 'app/utils/dates';

describe('utils/dates', function() {
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
        expect(result).toBeInstanceOf(Date);
        expect(toDateString(result)).toEqual(toDateString(new Date()));
      }
    });

    it('handles tomorrow', function() {
      const tomorrow = addDays(new Date(), 1);
      for (let option of ['tomorrow', 'Tomorrow', 'TOMORrow']) {
        const result = parseDateInput(option);
        expect(result).toBeInstanceOf(Date);
        expect(toDateString(result)).toEqual(toDateString(tomorrow));
      }
    });

    it('handles weekdays', function() {
      const now = new Date();
      let result = parseDateInput('Wednesday');
      expect(result.getDay()).toEqual(3);
      expect(result.getTime()).toBeGreaterThan(now.getMonth());

      result = parseDateInput('sunday');
      expect(result.getDay()).toEqual(1);
      expect(result.getTime()).toBeGreaterThan(now.getMonth());
    });

    it('handles wrapping days in the past', function() {
      const inPast = addMonths(new Date(), -2);
      const pastMonth = format(inPast, 'MMM');
      const result = parseDateInput(`${pastMonth} 10`);
      expect(result.getDate()).toEqual(10);
      expect(result.getFullYear()).toBeGreaterThan(inPast.getFullYear());
    });
  });
});
