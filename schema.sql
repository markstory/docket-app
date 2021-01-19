CREATE TABLE IF NOT EXISTS users (
    id int not null auto_increment primary key,
    email varchar(255) not null,
    email_verified boolean not null default false,
    password varchar(255) not null,
    name varchar(255) not null default '',
    unverified_email varchar(255) not null default '',
    timezone varchar(255) default 'UTC',
    created timestamp default current_timestamp,
    modified timestamp default current_timestamp on update current_timestamp
) CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Could add organizations and teams. Then projects would be owned
-- by teams inside organizations.

CREATE TABLE IF NOT EXISTS projects (
    id int not null auto_increment primary key,
    user_id int not null,
    name varchar(255) not null,
    slug varchar(255) not null,
    color int not null,
    favorite boolean not null default 0,
    archived boolean not null default 0,
    ranking int not null default 0,
    incomplete_task_count int not null default 0,
    created timestamp default current_timestamp,
    modified timestamp default current_timestamp on update current_timestamp,
    foreign key (user_id) references users(id) on delete cascade
) CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tasks (
    id int not null auto_increment primary key,
    project_id integer not null,
    title text,
    body text,
    due_on date,
    child_order integer not null default 0,
    day_order integer not null default 0,
    completed boolean not null default 0,
    subtask_count int not null default 0,
    incomplete_subtask_count int not null default 0,
    created timestamp default current_timestamp,
    modified timestamp default current_timestamp on update current_timestamp,
    foreign key (project_id) references projects(id) on delete cascade
) CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subtasks (
    id int not null auto_increment primary key,
    task_id integer not null,
    title text,
    body text,
    completed boolean not null default 0,
    created timestamp default current_timestamp,
    modified timestamp default current_timestamp on update current_timestamp,
    foreign key (task_id) references tasks(id) on delete cascade
) CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE labels (
    id int not null auto_increment primary key,
    project_id int not null,
    label varchar(50) not null,
    color char(6) not null,
    created timestamp default current_timestamp,
    modified timestamp default current_timestamp on update current_timestamp,
    foreign key (project_id) references projects(id) on delete cascade
) CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE labels_tasks (
    task_id int not null,
    label_id int not null,
    foreign key (task_id) references tasks(id) on delete cascade,
    foreign key (label_id) references labels(id) on delete cascade,
    primary key (task_id, todo_label_id)
) CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
