<?php

namespace Utopia\Validator;

use PHPUnit\Framework\TestCase;

class GlobSpecTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Section 1: Literal matching
    // -----------------------------------------------------------------------

    public function testLiteralMatching(): void
    {
        // 1.1 Exact match
        $this->assertTrue((new Glob(['file.txt']))->isValid('file.txt'));

        // 1.2 Literal non-match with extra suffix
        $this->assertFalse((new Glob(['file.txt']))->isValid('file.txt.bak'));
    }

    // -----------------------------------------------------------------------
    // Section 2: Single wildcard *
    // -----------------------------------------------------------------------

    public function testSingleWildcard(): void
    {
        // 2.1 Star matches any filename
        $this->assertTrue((new Glob(['*.txt']))->isValid('file.txt'));

        // 2.2 Star matches any filename (second case)
        $this->assertTrue((new Glob(['*.txt']))->isValid('another.txt'));

        // 2.3 Star does not match partial extension
        $this->assertFalse((new Glob(['*.txt']))->isValid('file.txt.bak'));

        // 2.4 Star does not cross directory boundaries
        $this->assertFalse((new Glob(['*.txt']))->isValid('dir/file.txt'));

        // 2.5 Star matches any segment within a path level
        $this->assertTrue((new Glob(['baz/*.txt']))->isValid('baz/file.txt'));

        // 2.6 Star does not match across directories in path-prefixed pattern
        $this->assertFalse((new Glob(['baz/*.txt']))->isValid('a/baz/file.txt'));

        // 2.7 Star does not match non-matching extension
        $this->assertFalse((new Glob(['baz/*.txt']))->isValid('baz/file.log'));
    }

    // -----------------------------------------------------------------------
    // Section 3: Question mark ?
    // -----------------------------------------------------------------------

    public function testQuestionMark(): void
    {
        // 3.1 Question mark matches single character
        $this->assertTrue((new Glob(['file.?xt']))->isValid('file.txt'));

        // 3.2 Question mark matches any single character
        $this->assertTrue((new Glob(['file.?xt']))->isValid('file.dxt'));

        // 3.3 Question mark does not match two characters
        $this->assertFalse((new Glob(['file.?xt']))->isValid('file.xtt'));

        // 3.4 Question mark used in directory pattern (single char suffix)
        $this->assertTrue((new Glob(['qux?']))->isValid('qux1'));

        // 3.5 Question mark matches any single character in suffix
        $this->assertTrue((new Glob(['qux?']))->isValid('quxa'));

        // 3.6 Question mark requires exactly one character
        $this->assertFalse((new Glob(['qux?']))->isValid('qux'));

        // 3.7 Question mark does not match two characters
        $this->assertFalse((new Glob(['qux?']))->isValid('qux12'));
    }

    // -----------------------------------------------------------------------
    // Section 4: Double wildcard **
    // -----------------------------------------------------------------------

    public function testDoubleWildcard(): void
    {
        // 4.1 **/file matches file at root (zero leading dirs)
        $this->assertTrue((new Glob(['**/file.txt']))->isValid('file.txt'));

        // 4.2 **/file matches file one directory deep
        $this->assertTrue((new Glob(['**/file.txt']))->isValid('dir/file.txt'));

        // 4.3 **/file matches file in nested directories
        $this->assertTrue((new Glob(['**/file.txt']))->isValid('dir/subdir/file.txt'));

        // 4.4 **/file does not match file with extra extension
        $this->assertFalse((new Glob(['**/file.txt']))->isValid('file.txt.bak'));

        // 4.5 src/**/file matches file directly under prefix
        $this->assertTrue((new Glob(['src/**/file.txt']))->isValid('src/file.txt'));

        // 4.6 src/**/file matches file one level deep in prefix
        $this->assertTrue((new Glob(['src/**/file.txt']))->isValid('src/dir/file.txt'));

        // 4.7 src/**/file matches file in nested dirs inside prefix
        $this->assertTrue((new Glob(['src/**/file.txt']))->isValid('src/dir/subdir/file.txt'));

        // 4.8 src/**/file does not match outside prefix
        $this->assertFalse((new Glob(['src/**/file.txt']))->isValid('other/file.txt'));

        // 4.9 **/name matches at root
        $this->assertTrue((new Glob(['**/temp.txt']))->isValid('temp.txt'));

        // 4.10 **/name matches one directory deep
        $this->assertTrue((new Glob(['**/temp.txt']))->isValid('a/temp.txt'));

        // 4.11 **/name matches two directories deep
        $this->assertTrue((new Glob(['**/temp.txt']))->isValid('a/b/temp.txt'));

        // 4.12 prefix/** matches direct child
        $this->assertTrue((new Glob(['src/**']))->isValid('src/file.txt'));

        // 4.13 prefix/** matches nested child
        $this->assertTrue((new Glob(['src/**']))->isValid('src/a/file.txt'));

        // 4.14 prefix/**/*.ext matches direct child with extension
        $this->assertTrue((new Glob(['src/**/*.log']))->isValid('src/error.log'));

        // 4.15 prefix/**/*.ext matches nested child with extension
        $this->assertTrue((new Glob(['src/**/*.log']))->isValid('src/a/debug.log'));

        // 4.16 a/**/b/c matches zero intermediate dirs
        $this->assertTrue((new Glob(['a/**/b/c']))->isValid('a/b/c'));

        // 4.17 a/**/b/c matches one intermediate dir
        $this->assertTrue((new Glob(['a/**/b/c']))->isValid('a/x/b/c'));

        // 4.18 a/**/b/c matches two intermediate dirs
        $this->assertTrue((new Glob(['a/**/b/c']))->isValid('a/x/y/b/c'));

        // 4.19 a/**/b/c does not match wrong tail
        $this->assertFalse((new Glob(['a/**/b/c']))->isValid('a/b/d'));

        // 4.20 **/d/e/** matches direct children of d/e at root
        $this->assertTrue((new Glob(['**/d/e/**']))->isValid('d/e/file.txt'));

        // 4.21 **/d/e/** matches when d/e is one level deep
        $this->assertTrue((new Glob(['**/d/e/**']))->isValid('x/d/e/file.txt'));

        // 4.22 **/d/e/** matches deep nesting around d/e
        $this->assertTrue((new Glob(['**/d/e/**']))->isValid('x/y/d/e/z/file.txt'));

        // 4.23 **/d/e/** does not match when path differs
        $this->assertFalse((new Glob(['**/d/e/**']))->isValid('d/f/file.txt'));

        // 4.24 prefix/**/*.ext path wildcard matches nested file
        $this->assertTrue((new Glob(['src/foo/**/*.js']))->isValid('src/foo/app/file.js'));

        // 4.25 src/foo/**/*.js matches direct child
        $this->assertTrue((new Glob(['src/foo/**/*.js']))->isValid('src/foo/file.js'));

        // 4.26 src/foo/**/*.js does not match outside prefix
        $this->assertFalse((new Glob(['src/foo/**/*.js']))->isValid('src/bar/app/file.js'));

        // 4.27 Deep nesting with **/logs/*.log
        $this->assertTrue((new Glob(['deep/**/logs/*.log']))->isValid('deep/level1/level2/level3/level4/level5/level6/level7/logs/app.log'));
    }

    // -----------------------------------------------------------------------
    // Section 5: Escaped characters
    // -----------------------------------------------------------------------

    public function testEscapedCharacters(): void
    {
        // 5.1 Escaped asterisk matches literal *
        $this->assertTrue((new Glob(['file\*.txt']))->isValid('file*.txt'));

        // 5.2 Escaped asterisk does not match regular character
        $this->assertFalse((new Glob(['file\*.txt']))->isValid('fileX.txt'));

        // 5.3 Escaped # is not treated as a comment
        $this->assertTrue((new Glob(['\#not_a_comment.txt']))->isValid('#not_a_comment.txt'));

        // 5.4 Escaped ? matches literal ?
        $this->assertTrue((new Glob(['file\?.txt']))->isValid('file?.txt'));
    }

    // Section 6: brace expansion — not supported

    // -----------------------------------------------------------------------
    // Section 7: Basic character classes [...]
    // -----------------------------------------------------------------------

    public function testBasicCharacterClasses(): void
    {
        // 7.1 Single character class matches
        $this->assertTrue((new Glob(['[a]bc.txt']))->isValid('abc.txt'));

        // 7.2 Single character class does not match other char
        $this->assertFalse((new Glob(['[a]bc.txt']))->isValid('bbc.txt'));

        // 7.3 Range [a-z] matches lowercase letter
        $this->assertTrue((new Glob(['[a-z]est.txt']))->isValid('test.txt'));

        // 7.4 Range [a-z] does not match uppercase
        $this->assertFalse((new Glob(['[a-z]est.txt']))->isValid('Test.txt'));

        // 7.5 Range [A-Z] matches uppercase letter
        $this->assertTrue((new Glob(['[A-Z]est.txt']))->isValid('Test.txt'));

        // 7.6 Range [A-Z] does not match lowercase
        $this->assertFalse((new Glob(['[A-Z]est.txt']))->isValid('test.txt'));

        // 7.7 Numeric range [0-9] matches digit
        $this->assertTrue((new Glob(['file[0-9].log']))->isValid('file5.log'));

        // 7.8 Numeric range [0-9] does not match letter
        $this->assertFalse((new Glob(['file[0-9].log']))->isValid('fileA.log'));

        // 7.9 Combined ranges [a-zA-Z] matches lowercase
        $this->assertTrue((new Glob(['[a-zA-Z]file.txt']))->isValid('afile.txt'));

        // 7.10 Combined ranges [a-zA-Z] matches uppercase
        $this->assertTrue((new Glob(['[a-zA-Z]file.txt']))->isValid('Afile.txt'));

        // 7.11 Combined ranges [a-zA-Z] does not match digit
        $this->assertFalse((new Glob(['[a-zA-Z]file.txt']))->isValid('1file.txt'));
    }

    // -----------------------------------------------------------------------
    // Section 8: Negated character classes [!...] / [^...]
    // -----------------------------------------------------------------------

    public function testNegatedCharacterClasses(): void
    {
        // 8.1 [!a-z] matches non-lowercase
        $this->assertTrue((new Glob(['[!a-z]file.txt']))->isValid('Afile.txt'));

        // 8.2 [!a-z] does not match lowercase
        $this->assertFalse((new Glob(['[!a-z]file.txt']))->isValid('afile.txt'));

        // 8.3 [^a-z] matches digit (caret negation syntax)
        $this->assertTrue((new Glob(['[^a-z]file.txt']))->isValid('1file.txt'));

        // 8.4 [^a-z] does not match lowercase (caret syntax)
        $this->assertFalse((new Glob(['[^a-z]file.txt']))->isValid('afile.txt'));

        // 8.5 [!a-z0-9] matches special character
        $this->assertTrue((new Glob(['[!a-z0-9]file.txt']))->isValid('#file.txt'));

        // 8.6 [!a-z0-9] does not match lowercase
        $this->assertFalse((new Glob(['[!a-z0-9]file.txt']))->isValid('afile.txt'));

        // 8.7 [!a-z0-9] does not match digit
        $this->assertFalse((new Glob(['[!a-z0-9]file.txt']))->isValid('5file.txt'));
    }

    // -----------------------------------------------------------------------
    // Section 9: Special characters inside classes
    // -----------------------------------------------------------------------

    public function testSpecialCharactersInsideClasses(): void
    {
        // 9.1 [.+] matches literal dot
        $this->assertTrue((new Glob(['file[.+]name.txt']))->isValid('file.name.txt'));

        // 9.2 [.+] matches literal plus
        $this->assertTrue((new Glob(['file[.+]name.txt']))->isValid('file+name.txt'));

        // 9.3 [.+] does not match empty
        $this->assertFalse((new Glob(['file[.+]name.txt']))->isValid('filename.txt'));

        // 9.4 [_!@#] matches underscore
        $this->assertTrue((new Glob(['[_!@#]special.txt']))->isValid('_special.txt'));

        // 9.5 [_!@#] matches at-sign
        $this->assertTrue((new Glob(['[_!@#]special.txt']))->isValid('@special.txt'));

        // 9.6 [_!@#] does not match unlisted char
        $this->assertFalse((new Glob(['[_!@#]special.txt']))->isValid('xspecial.txt'));

        // 9.7 Dash at start [-abc] matches literal dash
        $this->assertTrue((new Glob(['[-abc]dash.txt']))->isValid('-dash.txt'));

        // 9.8 Dash at start [-abc] matches listed char
        $this->assertTrue((new Glob(['[-abc]dash.txt']))->isValid('adash.txt'));

        // 9.9 Dash at end [abc-] matches literal dash
        $this->assertTrue((new Glob(['[abc-]dash.txt']))->isValid('-dash.txt'));
    }

    // -----------------------------------------------------------------------
    // Section 10: Character classes combined with other features
    // -----------------------------------------------------------------------

    public function testCharacterClassesCombined(): void
    {
        // 10.1 [a-z]* matches lowercase-prefixed filename
        $this->assertTrue((new Glob(['[a-z]*.txt']))->isValid('abc.txt'));

        // 10.2 [a-z]* does not match uppercase-prefixed filename
        $this->assertFalse((new Glob(['[a-z]*.txt']))->isValid('Abc.txt'));

        // 10.3 **/[a-z]* matches lowercase file in subdir
        $this->assertTrue((new Glob(['**/[a-z]*.txt']))->isValid('dir/abc.txt'));

        // 10.4 **/[a-z]* matches lowercase file deeply nested
        $this->assertTrue((new Glob(['**/[a-z]*.txt']))->isValid('dir/subdir/abc.txt'));

        // 10.5 **/[a-z]* does not match uppercase file in subdir
        $this->assertFalse((new Glob(['**/[a-z]*.txt']))->isValid('dir/Abc.txt'));

        // 10.6 Multiple character classes — both constraints satisfied
        $this->assertTrue((new Glob(['[a-z][0-9]*.txt']))->isValid('a1file.txt'));

        // 10.7 Multiple character classes — digit constraint fails
        $this->assertFalse((new Glob(['[a-z][0-9]*.txt']))->isValid('ab.txt'));

        // 10.8 Multiple character classes — letter constraint fails
        $this->assertFalse((new Glob(['[a-z][0-9]*.txt']))->isValid('A1file.txt'));
    }

    // -----------------------------------------------------------------------
    // Section 11: Character class edge cases
    // -----------------------------------------------------------------------

    public function testCharacterClassEdgeCases(): void
    {
        // 11.1 Empty class [] matches nothing
        $this->assertFalse((new Glob(['[]file.txt']))->isValid('file.txt'));

        // 11.2 Unclosed bracket is treated as literal characters
        $this->assertTrue((new Glob(['[abc']))->isValid('[abc'));

        // 11.3 Unclosed bracket does not match without the bracket
        $this->assertFalse((new Glob(['[abc']))->isValid('abc'));

        // 11.4 [!] — exclamation as only content, should match ! // edge case: behaviour may be implementation-defined
        $this->assertTrue((new Glob(['[!]file.txt']))->isValid('!file.txt'));

        // 11.5 [^] — caret as only content, should match ^ // edge case: behaviour may be implementation-defined
        $this->assertTrue((new Glob(['[^]file.txt']))->isValid('^file.txt'));
    }

    // -----------------------------------------------------------------------
    // Section 12: Complex / real-world patterns
    // -----------------------------------------------------------------------

    public function testComplexPatterns(): void
    {
        $patterns = ['*.md', '!README*.md', 'README-private*.md'];

        // 12.1 README negation chain — ignore all .md
        // documentation.md matches *.md (inclusion) and is not excluded → valid
        $this->assertTrue((new Glob($patterns))->isValid('documentation.md'));

        // 12.2 README negation chain — keep README
        // README.md matches *.md (inclusion) but is excluded by !README*.md → invalid
        $this->assertFalse((new Glob($patterns))->isValid('README.md'));

        // 12.3 README negation chain — keep README-public
        // README-public.md matches *.md but excluded by !README*.md → invalid
        $this->assertFalse((new Glob($patterns))->isValid('README-public.md'));

        // 12.4 README negation chain — ignore README-private
        // README-private.md: excluded by !README*.md but re-included by README-private*.md → valid
        $this->assertTrue((new Glob($patterns))->isValid('README-private.md'));

        // 12.5 README negation chain — ignore README-private-draft
        // README-private-draft.md: excluded by !README*.md but re-included by README-private*.md → valid
        $this->assertTrue((new Glob($patterns))->isValid('README-private-draft.md'));
    }
}
