import assert from 'node:assert/strict'
import { test } from 'node:test'
import { parseMathContent, serializeMathContent } from '../../src/math-content.js'

test('parses existing inline and display formulas into editable content blocks', () => {
  assert.deepEqual(parseMathContent('Luasnya $x^2$ dan kelilingnya \\[2\\pi r\\].'), [
    { type: 'text', content: 'Luasnya ' },
    { type: 'formula', value: 'x^2', display: false },
    { type: 'text', content: ' dan kelilingnya ' },
    { type: 'formula', value: '2\\pi r', display: true },
    { type: 'text', content: '.' },
  ])
})

test('serializes edited formulas back into content supported by the renderer', () => {
  const blocks = [
    { type: 'text', content: 'Tentukan nilai ' },
    { type: 'formula', value: '\\frac{1}{2}', display: false },
    { type: 'text', content: ' dari ' },
    { type: 'formula', value: '\\sqrt{16}', display: true },
  ]

  assert.equal(serializeMathContent(blocks), 'Tentukan nilai \\(\\frac{1}{2}\\) dari \\[\\sqrt{16}\\]')
})

test('round trips a mixed question and answer content string', () => {
  const source = 'Pilihan A: $x+1$; pembahasan memakai \\(x=3\\).'

  assert.equal(serializeMathContent(parseMathContent(source)), 'Pilihan A: \\(x+1\\); pembahasan memakai \\(x=3\\).')
})
