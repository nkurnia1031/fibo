const formulaPattern = /\\\(([\s\S]*?)\\\)|\\\[([\s\S]*?)\\\]|\$\$([\s\S]*?)\$\$|\$([^$\n]+?)\$/g

export function parseMathContent(source = '') {
  const content = String(source ?? '')
  const blocks = []
  let cursor = 0

  for (const match of content.matchAll(formulaPattern)) {
    const start = match.index
    if (start > cursor) blocks.push({ type: 'text', content: content.slice(cursor, start) })

    const display = match[2] !== undefined || match[3] !== undefined
    blocks.push({
      type: 'formula',
      value: match[1] ?? match[2] ?? match[3] ?? match[4],
      display,
    })
    cursor = start + match[0].length
  }

  if (cursor < content.length) blocks.push({ type: 'text', content: content.slice(cursor) })
  if (blocks.length === 0) blocks.push({ type: 'text', content: '' })

  return blocks
}

export function serializeMathContent(blocks) {
  return blocks.map((block) => {
    if (block.type !== 'formula') return block.content ?? ''
    const delimiters = block.display ? ['\\[', '\\]'] : ['\\(', '\\)']
    return `${delimiters[0]}${block.value ?? ''}${delimiters[1]}`
  }).join('')
}
