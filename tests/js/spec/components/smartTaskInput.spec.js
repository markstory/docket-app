import {tokenize} from 'app/components/smarkTaskInput';

describe('SmartTaskInput > tokenize', function () {
  it('extracts project names', function () {
    expect(tokenize('some #home))
  });
});
