import { describe, expect, it } from 'vitest'
import { sanitizeHtml } from './sanitizeHtml'

describe('sanitizeHtml', () => {
  it('strips script blocks', () => {
    expect(sanitizeHtml('<p>ok</p><script>alert(1)</script>')).toBe('<p>ok</p>')
  })

  it('strips inline event handlers', () => {
    expect(sanitizeHtml('<img src="x.jpg" onerror="alert(1)">')).toBe('<img src="x.jpg">')
  })

  it('strips javascript: URLs but keeps normal links and tables', () => {
    expect(sanitizeHtml('<a href="javascript:alert(1)">x</a>')).toBe('<a>x</a>')
    expect(sanitizeHtml('<table><tr><td>41</td></tr></table>')).toBe('<table><tr><td>41</td></tr></table>')
  })
})
