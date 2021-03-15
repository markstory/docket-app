import React from 'react';
import userEvent from '@testing-library/user-event';
import {render, waitFor, screen} from '@testing-library/react';

import SmartTaskInput from 'app/components/smartTaskInput';
import {makeTask, makeProject} from '../../fixtures';

describe('SmartTaskInput', function () {
  const projects = [
    makeProject({id: 1, title: 'Work'}),
    makeProject({id: 2, title: 'Home'}),
  ];
  it('renders current values', function () {
    render(
      <SmartTaskInput
        defaultValue="Initial value"
        projects={projects}
        onChangeDate={jest.fn()}
        onChangeProject={jest.fn()}
      />
    );
    expect(screen.getByRole('textbox').value).toBe('Initial value');
  });

  it('updates plain text value with stripped text', async function () {
    render(
      <SmartTaskInput
        defaultValue="Initial value"
        projects={projects}
        onChangeDate={jest.fn()}
        onChangeProject={jest.fn()}
      />
    );
    const textbox = screen.getByRole('textbox');
    await userEvent.type(textbox, '{selectall}{del}#Work\tafter', {delay: 5});
    expect(screen.getByTestId('smart-task-value').value).toEqual('after');
  });

  it('triggers change on project select', async function () {
    const onChange = jest.fn();
    render(
      <SmartTaskInput
        defaultValue="Initial value"
        projects={projects}
        onChangeDate={jest.fn()}
        onChangeProject={onChange}
      />
    );
    const textbox = screen.getByRole('textbox');
    await userEvent.type(textbox, '{selectall}{del}#Work\tafter', {delay: 5});
    expect(onChange).toHaveBeenCalledWith(projects[0].id);
  });

  it('triggers change on date select', async function () {
    const onChange = jest.fn();
    render(
      <SmartTaskInput
        defaultValue="Initial value"
        projects={projects}
        onChangeProject={jest.fn()}
        onChangeDate={onChange}
      />
    );
    const textbox = screen.getByRole('textbox');
    await userEvent.type(textbox, '{selectall}{del}%Tomorrow\tafter', {delay: 5});
    expect(onChange).toHaveBeenCalledWith(expect.stringMatching(/^\d{4}-\d{2}-\d{2}$/));
  });
});
