import {renderHook, act} from '@testing-library/react-hooks';
import {useDefaultTaskValues} from 'app/providers/defaultTaskValues';

test('should set project_id', () => {
  const {result} = renderHook(() => useDefaultTaskValues());

  act(() => {
    result.current[1]({type: 'add', data: {project_id: 1}});
  });

  let state = result.current[0];
  expect(state.distilled.project_id).toEqual(1);
  expect(state.items).toHaveLength(1);

  act(() => {
    result.current[1]({type: 'add', data: {project_id: 2}});
  });

  state = result.current[0];
  expect(state.distilled.project_id).toEqual(1);
  expect(state.items).toHaveLength(2);
});

test('item add keeps lowest due_on', () => {
  const {result} = renderHook(() => useDefaultTaskValues());

  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-01'}});
  });
  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-09'}});
  });

  const state = result.current[0];
  expect(state.distilled.due_on).toBe('2021-09-01');
  expect(state.items).toHaveLength(2);
});

test('item add overwrites due_on with lower value', () => {
  const {result} = renderHook(() => useDefaultTaskValues());

  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-09'}});
  });
  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-01'}});
  });

  const state = result.current[0];
  expect(state.distilled.due_on).toBe('2021-09-01');
  expect(state.items).toHaveLength(2);
});

test('item remove moves due_on to lower value', () => {
  const {result} = renderHook(() => useDefaultTaskValues());

  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-02'}});
  });
  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-01'}});
  });
  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-03'}});
  });
  act(() => {
    result.current[1]({type: 'remove', data: {due_on: '2021-09-01'}});
  });

  const state = result.current[0];
  expect(state.distilled.due_on).toBe('2021-09-02');
  expect(state.items).toHaveLength(2);
});

test('item remove moves due_on to lower value', () => {
  const {result} = renderHook(() => useDefaultTaskValues());

  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-02'}});
  });
  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-01'}});
  });
  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-03'}});
  });
  act(() => {
    result.current[1]({type: 'remove', data: {due_on: '2021-09-01'}});
  });

  const state = result.current[0];
  expect(state.distilled.due_on).toBe('2021-09-02');
  expect(state.items).toHaveLength(2);
});

test('item add sets evening', () => {
  const {result} = renderHook(() => useDefaultTaskValues());

  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-01', evening: false}});
  });
  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-01', evening: true}});
  });

  const state = result.current[0];
  expect(state.distilled.evening).toBe(true);
});

test('item remove sets evening', () => {
  const {result} = renderHook(() => useDefaultTaskValues());

  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-01', evening: false}});
  });
  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-01', evening: true}});
  });
  act(() => {
    result.current[1]({type: 'remove', data: {due_on: '2021-09-01', evening: true}});
  });

  const state = result.current[0];
  expect(state.distilled.evening).toBe(false);
});

test('item add sets section', () => {
  const {result} = renderHook(() => useDefaultTaskValues());

  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-01', section_id: 1}});
  });
  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-01', section_id: 2}});
  });

  const state = result.current[0];
  expect(state.distilled.section_id).toBe(1);
});

test('item remove retains section', () => {
  const {result} = renderHook(() => useDefaultTaskValues());

  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-01', section_id: 1}});
  });
  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-01', section_id: 2}});
  });
  act(() => {
    result.current[1]({type: 'remove', data: {due_on: '2021-09-01', section_id: 2}});
  });

  const state = result.current[0];
  expect(state.distilled.section_id).toBe(1);
});

test('item remove updates section', () => {
  const {result} = renderHook(() => useDefaultTaskValues());

  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-01', section_id: 1}});
  });
  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-01', section_id: 9}});
  });
  act(() => {
    result.current[1]({type: 'add', data: {due_on: '2021-09-01', section_id: 3}});
  });
  act(() => {
    result.current[1]({type: 'remove', data: {due_on: '2021-09-01', section_id: 1}});
  });

  const state = result.current[0];
  expect(state.distilled.section_id).toBe(9);
});
