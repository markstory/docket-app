const {spawn} = require('child_process');

const servers = [
  {prefix: 'php', command: 'bin/cake', args: ['server']},
  {prefix: 'react', command: 'npm', args: ['run', 'dev']},
];

const processes = servers.map(command => {
  return {
    ...command,
    process: spawn(command.command, command.args),
  };
});

processes.forEach(({prefix, process}) => {
  process.stdout.on('data', data => {
    console.log(`${prefix} | ${data}`.trim());
  });
  process.stderr.on('data', data => {
    console.log(`${prefix} | ERR | ${data}`.trim());
  });
});

process.on('SIGINT', () => {
  console.log('Killing all processes..');
  processes.forEach(({command, process}) => {
    console.log(`Killing ${command}`);
    process.kill();
  });
  process.exit(2);
});
