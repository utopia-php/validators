# Glob Pattern Matching Specification

Derived exhaustively from every test case in the gregpriday/gitignore-php test suite.
Source files: `PatternConverterTest`, `AdvancedPatternConverterTest`, `GitIgnoreManagerTest`, `AdvancedGitIgnoreTest`.

Sections that test filesystem scanning (reading `.gitignore` files from disk, `SplFileInfo`, directory hierarchies, multiple ignore-file precedence) are marked **out of scope** for a pure pattern validator and omitted from requirements.

---

## 1. Literal matching

### 1.1 Exact match
- Pattern: `file.txt`
- Subject: `file.txt`
- Expected: match

### 1.2 Literal non-match with extra suffix
- Pattern: `file.txt`
- Subject: `file.txt.bak`
- Expected: no match

---

## 2. Single wildcard `*`

### 2.1 Star matches any filename
- Pattern: `*.txt`
- Subject: `file.txt`
- Expected: match

### 2.2 Star matches any filename (second case)
- Pattern: `*.txt`
- Subject: `another.txt`
- Expected: match

### 2.3 Star does not match partial extension
- Pattern: `*.txt`
- Subject: `file.txt.bak`
- Expected: no match

### 2.4 Star does not cross directory boundaries
- Pattern: `*.txt`
- Subject: `dir/file.txt`
- Expected: no match

### 2.5 Star matches any segment within a path level
- Pattern: `baz/*.txt`
- Subject: `baz/file.txt`
- Expected: match

### 2.6 Star does not match across directories in path-prefixed pattern
- Pattern: `baz/*.txt`
- Subject: `a/baz/file.txt`
- Expected: no match

### 2.7 Star does not match non-matching extension
- Pattern: `baz/*.txt`
- Subject: `baz/file.log`
- Expected: no match

---

## 3. Question mark `?`

### 3.1 Question mark matches single character
- Pattern: `file.?xt`
- Subject: `file.txt`
- Expected: match

### 3.2 Question mark matches any single character
- Pattern: `file.?xt`
- Subject: `file.dxt`
- Expected: match

### 3.3 Question mark does not match two characters
- Pattern: `file.?xt`
- Subject: `file.xtt`
- Expected: no match

### 3.4 Question mark used in directory pattern (single char suffix)
- Pattern: `qux?`
- Subject: `qux1`
- Expected: match

### 3.5 Question mark matches any single character in suffix
- Pattern: `qux?`
- Subject: `quxa`
- Expected: match

### 3.6 Question mark requires exactly one character
- Pattern: `qux?`
- Subject: `qux`
- Expected: no match

### 3.7 Question mark does not match two characters
- Pattern: `qux?`
- Subject: `qux12`
- Expected: no match

---

## 4. Double wildcard `**`

### 4.1 `**/file` matches file at root (zero leading dirs)
- Pattern: `**/file.txt`
- Subject: `file.txt`
- Expected: match

### 4.2 `**/file` matches file one directory deep
- Pattern: `**/file.txt`
- Subject: `dir/file.txt`
- Expected: match

### 4.3 `**/file` matches file in nested directories
- Pattern: `**/file.txt`
- Subject: `dir/subdir/file.txt`
- Expected: match

### 4.4 `**/file` does not match file with extra extension
- Pattern: `**/file.txt`
- Subject: `file.txt.bak`
- Expected: no match

### 4.5 `src/**/file` matches file directly under prefix
- Pattern: `src/**/file.txt`
- Subject: `src/file.txt`
- Expected: match

### 4.6 `src/**/file` matches file one level deep in prefix
- Pattern: `src/**/file.txt`
- Subject: `src/dir/file.txt`
- Expected: match

### 4.7 `src/**/file` matches file in nested dirs inside prefix
- Pattern: `src/**/file.txt`
- Subject: `src/dir/subdir/file.txt`
- Expected: match

### 4.8 `src/**/file` does not match outside prefix
- Pattern: `src/**/file.txt`
- Subject: `other/file.txt`
- Expected: no match

### 4.9 `**/name` matches at root
- Pattern: `**/temp.txt`
- Subject: `temp.txt`
- Expected: match

### 4.10 `**/name` matches one directory deep
- Pattern: `**/temp.txt`
- Subject: `a/temp.txt`
- Expected: match

### 4.11 `**/name` matches two directories deep
- Pattern: `**/temp.txt`
- Subject: `a/b/temp.txt`
- Expected: match

### 4.12 `prefix/**` matches direct child
- Pattern: `src/**`
- Subject: `src/file.txt`
- Expected: match

### 4.13 `prefix/**` matches nested child
- Pattern: `src/**`
- Subject: `src/a/file.txt`
- Expected: match

### 4.14 `prefix/**/*.ext` matches direct child with extension
- Pattern: `src/**/*.log`
- Subject: `src/error.log`
- Expected: match

### 4.15 `prefix/**/*.ext` matches nested child with extension
- Pattern: `src/**/*.log`
- Subject: `src/a/debug.log`
- Expected: match

### 4.16 `a/**/b/c` matches zero intermediate dirs
- Pattern: `a/**/b/c`
- Subject: `a/b/c`
- Expected: match

### 4.17 `a/**/b/c` matches one intermediate dir
- Pattern: `a/**/b/c`
- Subject: `a/x/b/c`
- Expected: match

### 4.18 `a/**/b/c` matches two intermediate dirs
- Pattern: `a/**/b/c`
- Subject: `a/x/y/b/c`
- Expected: match

### 4.19 `a/**/b/c` does not match wrong tail
- Pattern: `a/**/b/c`
- Subject: `a/b/d`
- Expected: no match

### 4.20 `**/d/e/**` matches direct children of d/e at root
- Pattern: `**/d/e/**`
- Subject: `d/e/file.txt`
- Expected: match

### 4.21 `**/d/e/**` matches when d/e is one level deep
- Pattern: `**/d/e/**`
- Subject: `x/d/e/file.txt`
- Expected: match

### 4.22 `**/d/e/**` matches deep nesting around d/e
- Pattern: `**/d/e/**`
- Subject: `x/y/d/e/z/file.txt`
- Expected: match

### 4.23 `**/d/e/**` does not match when path differs
- Pattern: `**/d/e/**`
- Subject: `d/f/file.txt`
- Expected: no match

### 4.24 `prefix/**/*.ext` path wildcard matches nested file
- Pattern: `src/foo/**/*.js`
- Subject: `src/foo/app/file.js`
- Expected: match

### 4.25 `src/foo/**/*.js` matches direct child
- Pattern: `src/foo/**/*.js`
- Subject: `src/foo/file.js`
- Expected: match

### 4.26 `src/foo/**/*.js` does not match outside prefix
- Pattern: `src/foo/**/*.js`
- Subject: `src/bar/app/file.js`
- Expected: no match

### 4.27 Deep nesting with `**/logs/*.log`
- Pattern: `deep/**/logs/*.log`
- Subject: `deep/level1/level2/level3/level4/level5/level6/level7/logs/app.log`
- Expected: match

---

## 5. Escaped characters `\`

### 5.1 Escaped asterisk matches literal `*`
- Pattern: `file\*.txt`
- Subject: `file*.txt`
- Expected: match

### 5.2 Escaped asterisk does not match regular character
- Pattern: `file\*.txt`
- Subject: `fileX.txt`
- Expected: no match

### 5.3 Escaped `#` is not treated as a comment
- Pattern: `\#not_a_comment.txt`
- Subject: `#not_a_comment.txt`
- Expected: match

### 5.4 Escaped `?` matches literal `?`
- Pattern: `file\?.txt`
- Subject: `file?.txt`
- Expected: match

---

## 6. Brace expansion `{a,b}`

> **Note:** Brace expansion is a shell glob extension, not part of the gitignore specification. Implementations may choose not to support it. If unsupported, patterns containing `{` should be treated as literals or produce no match for expanded variants. Tests in this section document the gregpriday library's behaviour only; they are **not required** for a gitignore-spec-compliant validator.

### 6.1 Simple brace expansion — css
- Pattern: `*.{js,css,html}`
- Subject: `style.css`
- Expected: match (if brace expansion supported)

### 6.2 Simple brace expansion — js
- Pattern: `*.{js,css,html}`
- Subject: `script.js`
- Expected: match (if brace expansion supported)

### 6.3 Simple brace expansion — html
- Pattern: `*.{js,css,html}`
- Subject: `page.html`
- Expected: match (if brace expansion supported)

### 6.4 Simple brace expansion — non-member extension
- Pattern: `*.{js,css,html}`
- Subject: `file.txt`
- Expected: no match

### 6.5 Star in brace pattern still does not cross `/`
- Pattern: `*.{js,css,html}`
- Subject: `dir/style.css`
- Expected: no match

### 6.6 Brace + `**` — direct child of prefix
- Pattern: `src/**/*.{js,css,html}`
- Subject: `src/style.css`
- Expected: match (if brace expansion supported)

### 6.7 Brace + `**` — one level deep
- Pattern: `src/**/*.{js,css,html}`
- Subject: `src/js/script.js`
- Expected: match (if brace expansion supported)

### 6.8 Brace + `**` — nested dirs
- Pattern: `src/**/*.{js,css,html}`
- Subject: `src/views/components/page.html`
- Expected: match (if brace expansion supported)

### 6.9 Brace + `**` — wrong extension
- Pattern: `src/**/*.{js,css,html}`
- Subject: `src/file.txt`
- Expected: no match

### 6.10 Brace + `**` — outside prefix
- Pattern: `src/**/*.{js,css,html}`
- Subject: `app/style.css`
- Expected: no match

### 6.11 Nested brace — src/js/file.js
- Pattern: `{src,app}/js/*.{js,{ts,tsx}}`
- Subject: `src/js/file.js`
- Expected: match (if brace expansion supported)

### 6.12 Nested brace — src/js/file.ts
- Pattern: `{src,app}/js/*.{js,{ts,tsx}}`
- Subject: `src/js/file.ts`
- Expected: match (if brace expansion supported)

### 6.13 Nested brace — src/js/file.tsx
- Pattern: `{src,app}/js/*.{js,{ts,tsx}}`
- Subject: `src/js/file.tsx`
- Expected: match (if brace expansion supported)

### 6.14 Nested brace — app/js/file.js
- Pattern: `{src,app}/js/*.{js,{ts,tsx}}`
- Subject: `app/js/file.js`
- Expected: match (if brace expansion supported)

### 6.15 Nested brace — app/js/file.ts
- Pattern: `{src,app}/js/*.{js,{ts,tsx}}`
- Subject: `app/js/file.ts`
- Expected: match (if brace expansion supported)

### 6.16 Nested brace — wrong top-level dir
- Pattern: `{src,app}/js/*.{js,{ts,tsx}}`
- Subject: `lib/js/file.js`
- Expected: no match

### 6.17 Nested brace — wrong extension
- Pattern: `{src,app}/js/*.{js,{ts,tsx}}`
- Subject: `src/js/file.css`
- Expected: no match

---

## 7. Basic character classes `[...]`

### 7.1 Single character class matches
- Pattern: `[a]bc.txt`
- Subject: `abc.txt`
- Expected: match

### 7.2 Single character class does not match other char
- Pattern: `[a]bc.txt`
- Subject: `bbc.txt`
- Expected: no match

### 7.3 Range `[a-z]` matches lowercase letter
- Pattern: `[a-z]est.txt`
- Subject: `test.txt`
- Expected: match

### 7.4 Range `[a-z]` does not match uppercase
- Pattern: `[a-z]est.txt`
- Subject: `Test.txt`
- Expected: no match

### 7.5 Range `[A-Z]` matches uppercase letter
- Pattern: `[A-Z]est.txt`
- Subject: `Test.txt`
- Expected: match

### 7.6 Range `[A-Z]` does not match lowercase
- Pattern: `[A-Z]est.txt`
- Subject: `test.txt`
- Expected: no match

### 7.7 Numeric range `[0-9]` matches digit
- Pattern: `file[0-9].log`
- Subject: `file5.log`
- Expected: match

### 7.8 Numeric range `[0-9]` does not match letter
- Pattern: `file[0-9].log`
- Subject: `fileA.log`
- Expected: no match

### 7.9 Combined ranges `[a-zA-Z]` matches lowercase
- Pattern: `[a-zA-Z]file.txt`
- Subject: `afile.txt`
- Expected: match

### 7.10 Combined ranges `[a-zA-Z]` matches uppercase
- Pattern: `[a-zA-Z]file.txt`
- Subject: `Afile.txt`
- Expected: match

### 7.11 Combined ranges `[a-zA-Z]` does not match digit
- Pattern: `[a-zA-Z]file.txt`
- Subject: `1file.txt`
- Expected: no match

---

## 8. Negated character classes `[!...]` / `[^...]`

### 8.1 `[!a-z]` matches non-lowercase
- Pattern: `[!a-z]file.txt`
- Subject: `Afile.txt`
- Expected: match

### 8.2 `[!a-z]` does not match lowercase
- Pattern: `[!a-z]file.txt`
- Subject: `afile.txt`
- Expected: no match

### 8.3 `[^a-z]` matches digit (caret negation syntax)
- Pattern: `[^a-z]file.txt`
- Subject: `1file.txt`
- Expected: match

### 8.4 `[^a-z]` does not match lowercase (caret syntax)
- Pattern: `[^a-z]file.txt`
- Subject: `afile.txt`
- Expected: no match

### 8.5 `[!a-z0-9]` matches special character
- Pattern: `[!a-z0-9]file.txt`
- Subject: `#file.txt`
- Expected: match

### 8.6 `[!a-z0-9]` does not match lowercase
- Pattern: `[!a-z0-9]file.txt`
- Subject: `afile.txt`
- Expected: no match

### 8.7 `[!a-z0-9]` does not match digit
- Pattern: `[!a-z0-9]file.txt`
- Subject: `5file.txt`
- Expected: no match

---

## 9. Special characters inside classes

### 9.1 `[.+]` matches literal dot
- Pattern: `file[.+]name.txt`
- Subject: `file.name.txt`
- Expected: match

### 9.2 `[.+]` matches literal plus
- Pattern: `file[.+]name.txt`
- Subject: `file+name.txt`
- Expected: match

### 9.3 `[.+]` does not match empty
- Pattern: `file[.+]name.txt`
- Subject: `filename.txt`
- Expected: no match

### 9.4 `[_!@#]` matches underscore
- Pattern: `[_!@#]special.txt`
- Subject: `_special.txt`
- Expected: match

### 9.5 `[_!@#]` matches at-sign
- Pattern: `[_!@#]special.txt`
- Subject: `@special.txt`
- Expected: match

### 9.6 `[_!@#]` does not match unlisted char
- Pattern: `[_!@#]special.txt`
- Subject: `xspecial.txt`
- Expected: no match

### 9.7 Dash at start `[-abc]` matches literal dash
- Pattern: `[-abc]dash.txt`
- Subject: `-dash.txt`
- Expected: match

### 9.8 Dash at start `[-abc]` matches listed char
- Pattern: `[-abc]dash.txt`
- Subject: `adash.txt`
- Expected: match

### 9.9 Dash at end `[abc-]` matches literal dash
- Pattern: `[abc-]dash.txt`
- Subject: `-dash.txt`
- Expected: match

---

## 10. Character classes combined with other features

### 10.1 `[a-z]*` matches lowercase-prefixed filename
- Pattern: `[a-z]*.txt`
- Subject: `abc.txt`
- Expected: match

### 10.2 `[a-z]*` does not match uppercase-prefixed filename
- Pattern: `[a-z]*.txt`
- Subject: `Abc.txt`
- Expected: no match

### 10.3 `**/[a-z]*` matches lowercase file in subdir
- Pattern: `**/[a-z]*.txt`
- Subject: `dir/abc.txt`
- Expected: match

### 10.4 `**/[a-z]*` matches lowercase file deeply nested
- Pattern: `**/[a-z]*.txt`
- Subject: `dir/subdir/abc.txt`
- Expected: match

### 10.5 `**/[a-z]*` does not match uppercase file in subdir
- Pattern: `**/[a-z]*.txt`
- Subject: `dir/Abc.txt`
- Expected: no match

### 10.6 Multiple character classes — both constraints satisfied
- Pattern: `[a-z][0-9]*.txt`
- Subject: `a1file.txt`
- Expected: match

### 10.7 Multiple character classes — digit constraint fails
- Pattern: `[a-z][0-9]*.txt`
- Subject: `ab.txt`
- Expected: no match

### 10.8 Multiple character classes — letter constraint fails
- Pattern: `[a-z][0-9]*.txt`
- Subject: `A1file.txt`
- Expected: no match

---

## 11. Character class edge cases

### 11.1 Empty class `[]` matches nothing
- Pattern: `[]file.txt`
- Subject: `file.txt`
- Expected: no match
- Notes: `[]` has no valid character class content; the `[` should not cause a match on the bare filename.

### 11.2 Unclosed bracket is treated as literal characters
- Pattern: `[abc`
- Subject: `[abc`
- Expected: match
- Notes: No closing `]` found; the whole token is treated as literal characters.

### 11.3 Unclosed bracket does not match without the bracket
- Pattern: `[abc`
- Subject: `abc`
- Expected: no match

### 11.4 `[!]` — exclamation as only content, should match `!`
- Pattern: `[!]file.txt`
- Subject: `!file.txt`
- Expected: match
- Notes: `]` is the first char after `!`, making it part of the class rather than the closing bracket; the class ends up matching `!` literally. Behaviour is implementation-defined; some implementations treat this as an unclosed class.

### 11.5 `[^]` — caret as only content, should match `^`
- Pattern: `[^]file.txt`
- Subject: `^file.txt`
- Expected: match
- Notes: Same edge-case reasoning as 11.4.

---

## 12. Complex / real-world patterns

### 12.1 README negation chain — ignore all `.md`
- Pattern list: `*.md`, `!README*.md`, `README-private*.md`
- Subject: `documentation.md`
- Expected: excluded (matched by `*.md`, not re-included)

### 12.2 README negation chain — keep README
- Pattern list: `*.md`, `!README*.md`, `README-private*.md`
- Subject: `README.md`
- Expected: included (re-included by `!README*.md`)

### 12.3 README negation chain — keep README-public
- Pattern list: `*.md`, `!README*.md`, `README-private*.md`
- Subject: `README-public.md`
- Expected: included

### 12.4 README negation chain — ignore README-private
- Pattern list: `*.md`, `!README*.md`, `README-private*.md`
- Subject: `README-private.md`
- Expected: excluded (overridden by `README-private*.md`)

### 12.5 README negation chain — ignore README-private-draft
- Pattern list: `*.md`, `!README*.md`, `README-private*.md`
- Subject: `README-private-draft.md`
- Expected: excluded

---

## Out of scope

The following test classes test **filesystem-level behaviour** of `GitIgnoreManager` and are not requirements for a pure pattern validator:

- Reading `.gitignore` files from disk
- Hierarchical / nested `.gitignore` files with different scopes per directory
- Leading-slash anchoring (pattern `/config` only matches at the directory where the `.gitignore` lives)
- Directory-only patterns (trailing `/`)
- `SplFileInfo` acceptance logic
- Multiple custom ignore-file types (`.dockerignore`, `.customignore`)
- Case-insensitive matching mode
- Whitespace trimming from pattern lines
- Comment lines (`#`) in ignore files
- Quoted patterns (`"..."` in ignore files)
- Unicode filenames (out of scope unless the underlying regex/fnmatch engine supports them natively)
- Multiple-negation counting (`!!` → re-ignore, `!!!` → re-include)
