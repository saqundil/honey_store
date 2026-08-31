// يشغّل معاينة المحرر في Node بـDOM وهمي، لالتقاط HTML الجدول الذي تبنيه فعلًا
const fs = require('fs');

const captured = {};
const makeNode = (id) => {
  const node = {
    _id: id,
    classList: { add() {}, remove() {}, toggle() { return false; }, contains() { return false; } },
    style: {}, dataset: {}, children: [], attributes: {},
    setAttribute() {}, getAttribute() { return null; }, addEventListener() {},
    appendChild() {}, removeChild() {}, insertBefore() {}, remove() {},
    querySelector: () => makeNode('child'), querySelectorAll: () => [],
    closest: () => null, focus() {}, blur() {},
    get innerHTML() { return captured[id] || ''; },
    set innerHTML(value) { captured[id] = value; },
    textContent: '', value: '', checked: false, disabled: false, type: '',
  };
  return node;
};

const nodes = new Map();
const get = (selector) => {
  if (!nodes.has(selector)) nodes.set(selector, makeNode(selector));
  return nodes.get(selector);
};

global.document = {
  querySelector: (selector) => get(selector),
  querySelectorAll: () => [],
  createElement: () => makeNode('created'),
  addEventListener() {},
  body: makeNode('body'),
};
global.window = { BUILDER_DATA: JSON.parse(fs.readFileSync(process.argv[2], 'utf8')), APP: { baseUrl: '', csrf: '' } };
global.APP = global.window.APP;
global.fetch = async () => ({ json: async () => ({ ok: true }) });
global.location = { href: '' };
global.confirm = () => true;
global.alert = () => {};

eval(fs.readFileSync(process.argv[3], 'utf8'));

process.stdout.write(captured['#live-preview'] || '(empty)');
