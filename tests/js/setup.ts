function assertDefined<T>(maybe: T | undefined): asserts maybe {
  if (typeof maybe === 'undefined' || maybe === null) {
    throw new Error('Unexpected value. Did not expect null | undefined');
  }
}

global.assertDefined = assertDefined;
