import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import {TodoItem} from 'app/types';
import TodoItemQuickForm from 'app/components/todoItemQuickForm';

type Props = {
  onCancel: () => void;
  defaultDate?: string;
  defaultProjectId?: number;
};

function QuickAddTaskForm({onCancel, defaultDate, defaultProjectId}: Props) {
  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    e.persist();

    const form = e.target as HTMLFormElement;
    const formData = new FormData(form);

    Inertia.post('/todos/add', formData, {
      onSuccess: () => onCancel(),
    });
  };
  const todoItem: TodoItem = {
    id: -1,
    title: '',
    body: '',
    due_on: defaultDate ?? null,
    completed: false,
    created: '',
    modified: '',
    project: {
      id: defaultProjectId ? Number(defaultProjectId) : -1,
      name: '',
      slug: '',
      color: '',
      favorite: false,
      archived: false,
    },
  };

  return (
    <TodoItemQuickForm todoItem={todoItem} onSubmit={onSubmit} onCancel={onCancel} />
  );
}
export default QuickAddTaskForm;
