// Select elements
const todoInput = document.getElementById('todo-input');
const addTaskButton = document.getElementById('add-task');
const todoList = document.getElementById('todo-list');

// Add task functionality
addTaskButton.addEventListener('click', () => {
  const taskText = todoInput.value.trim();
  if (taskText === '') return; // Ignore empty input

  // Create a new list item
  const listItem = document.createElement('li');
  listItem.classList.add(
    'flex',
    'items-center',
    'justify-between',
    'px-4',
    'py-2',
    'border',
    'border-border_color',
    'rounded-lg',
    'group',
  );

  // Task content
  listItem.innerHTML = `
    <div class="flex items-center gap-3">
      <input
        type="checkbox"
        class="w-4 h-4 rounded border-border_color text-modern-primary focus:ring-blue-500"
      >
      <span>${taskText}</span>
    </div>
    <button
      class="text-modern-primary hover:text-modern-danger"
    >
      <i class="mdi mdi-close-circle-outline text-xl"></i>
    </button>
  `;

  // Append to the list
  todoList.prepend(listItem);

  // Clear the input
  todoInput.value = '';
});

// Handle task completion and removal
todoList.addEventListener('click', (e) => {
  const target = e.target;

  // If a checkbox is clicked, toggle the line-through class
  if (target.tagName === 'INPUT' && target.type === 'checkbox') {
    const text = target.nextElementSibling;
    text.classList.toggle('line-through');
  }

  // If a remove button is clicked, remove the task
  if (target.closest('button')) {
    const listItem = target.closest('li');
    listItem.remove();
  }
});
