<?php

namespace Utopia\Validator;

use PHPUnit\Framework\TestCase;

class GlobTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Empty patterns
    // -------------------------------------------------------------------------

    public function testEmptyPatternsAlwaysPass(): void
    {
        $validator = new Glob([]);
        $this->assertTrue($validator->isValid('main'));
        $this->assertTrue($validator->isValid('feature/anything'));
        $this->assertTrue($validator->isValid('src/deep/nested/file.php'));
    }

    // -------------------------------------------------------------------------
    // Pure inclusion — OR semantics (any one match is enough)
    // -------------------------------------------------------------------------

    public function testSingleExactInclusion(): void
    {
        $validator = new Glob(['main']);
        $this->assertTrue($validator->isValid('main'));
        $this->assertFalse($validator->isValid('develop'));
        $this->assertFalse($validator->isValid('main-extra'));
    }

    public function testMultipleExactInclusionsOr(): void
    {
        $validator = new Glob(['main', 'develop', 'staging']);
        $this->assertTrue($validator->isValid('main'));
        $this->assertTrue($validator->isValid('develop'));
        $this->assertTrue($validator->isValid('staging'));
        $this->assertFalse($validator->isValid('feature/foo'));
        $this->assertFalse($validator->isValid('production'));
    }

    public function testSingleWildcardInclusion(): void
    {
        $validator = new Glob(['feature/*']);
        $this->assertTrue($validator->isValid('feature/foo'));
        $this->assertTrue($validator->isValid('feature/bar'));
        $this->assertFalse($validator->isValid('feature/foo/bar')); // * does not cross /
        $this->assertFalse($validator->isValid('main'));
    }

    public function testWildcardWithDash(): void
    {
        $validator = new Glob(['feature/test-*']);
        $this->assertTrue($validator->isValid('feature/test-1'));
        $this->assertTrue($validator->isValid('feature/test-abc'));
        $this->assertFalse($validator->isValid('feature/other'));
        $this->assertFalse($validator->isValid('feature/test'));
    }

    public function testQuestionMarkWildcard(): void
    {
        $validator = new Glob(['v?.?']);
        $this->assertTrue($validator->isValid('v1.0'));
        $this->assertTrue($validator->isValid('v2.5'));
        $this->assertFalse($validator->isValid('v10.0')); // ? matches exactly one char, not two
        $this->assertFalse($validator->isValid('v1/0'));  // ? does not cross /
    }

    public function testQuestionMarkDoesNotCrossSlash(): void
    {
        $validator = new Glob(['feature/?']);
        $this->assertTrue($validator->isValid('feature/a'));
        $this->assertTrue($validator->isValid('feature/z'));
        $this->assertFalse($validator->isValid('feature/ab'));   // ? matches only one char
        $this->assertFalse($validator->isValid('feature/a/b')); // ? does not cross /
        $this->assertFalse($validator->isValid('feature/'));
    }

    public function testQuestionMarkMixedWithStar(): void
    {
        $validator = new Glob(['fix-?.*']);
        $this->assertTrue($validator->isValid('fix-1.php'));
        $this->assertTrue($validator->isValid('fix-a.js'));
        $this->assertFalse($validator->isValid('fix-12.php')); // ? matches only one char
        $this->assertFalse($validator->isValid('fix-.php'));   // ? requires exactly one char
    }

    public function testDoubleWildcardAtEnd(): void
    {
        $validator = new Glob(['src/**']);
        $this->assertTrue($validator->isValid('src/foo.js'));
        $this->assertTrue($validator->isValid('src/a/b/c.js'));
        $this->assertTrue($validator->isValid('src/deep/nested/file.php'));
        $this->assertFalse($validator->isValid('lib/foo.js'));
    }

    public function testDoubleWildcardInMiddle(): void
    {
        $validator = new Glob(['a/**/b']);
        $this->assertTrue($validator->isValid('a/b'));      // zero intermediate dirs
        $this->assertTrue($validator->isValid('a/x/b'));    // one
        $this->assertTrue($validator->isValid('a/x/y/b')); // two
        $this->assertFalse($validator->isValid('a/b/c'));
        $this->assertFalse($validator->isValid('x/a/b'));
    }

    public function testDoubleWildcardAtStart(): void
    {
        $validator = new Glob(['**/foo']);
        $this->assertTrue($validator->isValid('foo'));       // zero leading dirs
        $this->assertTrue($validator->isValid('a/foo'));     // one
        $this->assertTrue($validator->isValid('a/b/foo'));   // two
        $this->assertFalse($validator->isValid('foobar'));
        $this->assertFalse($validator->isValid('a/foobar'));
    }

    public function testMixedExactAndWildcardInclusions(): void
    {
        $validator = new Glob(['main', 'feature/*']);
        $this->assertTrue($validator->isValid('main'));
        $this->assertTrue($validator->isValid('feature/foo'));
        $this->assertFalse($validator->isValid('develop'));
        $this->assertFalse($validator->isValid('feature/foo/bar'));
    }

    // -------------------------------------------------------------------------
    // Pure exclusion — AND semantics (must not match any exclusion)
    // -------------------------------------------------------------------------

    public function testSingleExactExclusion(): void
    {
        $validator = new Glob(['!main']);
        $this->assertFalse($validator->isValid('main'));
        $this->assertTrue($validator->isValid('develop'));
        $this->assertTrue($validator->isValid('feature/foo'));
    }

    public function testMultipleExactExclusionsAnd(): void
    {
        $validator = new Glob(['!main', '!develop']);
        $this->assertFalse($validator->isValid('main'));
        $this->assertFalse($validator->isValid('develop'));
        $this->assertTrue($validator->isValid('staging'));
        $this->assertTrue($validator->isValid('feature/foo'));
    }

    public function testWildcardExclusion(): void
    {
        $validator = new Glob(['!feature/*']);
        $this->assertFalse($validator->isValid('feature/foo'));
        $this->assertFalse($validator->isValid('feature/bar'));
        $this->assertTrue($validator->isValid('main'));
        $this->assertTrue($validator->isValid('hotfix/urgent'));
    }

    public function testDoubleWildcardExclusion(): void
    {
        $validator = new Glob(['!src/**']);
        $this->assertFalse($validator->isValid('src/foo.js'));
        $this->assertFalse($validator->isValid('src/a/b/c.js'));
        $this->assertTrue($validator->isValid('lib/foo.js'));
        $this->assertTrue($validator->isValid('main'));
    }

    // -------------------------------------------------------------------------
    // Mixed inclusion + exclusion
    // -------------------------------------------------------------------------

    public function testInclusionTakesPrecedenceWhenBothMatch(): void
    {
        $validator = new Glob(['!feature/*', 'feature/abc']);
        $this->assertTrue($validator->isValid('feature/abc'));  // inclusion wins
        $this->assertFalse($validator->isValid('feature/xyz')); // only exclusion matches
        $this->assertFalse($validator->isValid('main'));        // no inclusion matches
    }

    public function testInclusionWithNoMatchFails(): void
    {
        $validator = new Glob(['main', '!develop']);
        $this->assertTrue($validator->isValid('main'));
        $this->assertFalse($validator->isValid('develop'));  // excluded even if inclusion didn't match
        $this->assertFalse($validator->isValid('staging')); // no inclusion match
    }

    public function testExclusionBlocksWhenInclusionDoesNotMatch(): void
    {
        $validator = new Glob(['feature/*', '!hotfix/*']);
        $this->assertTrue($validator->isValid('feature/foo'));
        $this->assertFalse($validator->isValid('hotfix/urgent')); // no inclusion match, also excluded
        $this->assertFalse($validator->isValid('main'));          // no inclusion match
    }

    public function testMultipleInclusionsWithSingleExclusion(): void
    {
        $validator = new Glob(['main', 'develop', 'feature/*', '!feature/wip']);
        $this->assertTrue($validator->isValid('main'));
        $this->assertTrue($validator->isValid('develop'));
        $this->assertTrue($validator->isValid('feature/foo'));
        $this->assertFalse($validator->isValid('feature/wip'));   // specific exclusion overrides wildcard inclusion
        $this->assertFalse($validator->isValid('hotfix/urgent')); // no inclusion match
    }

    public function testSingleInclusionWithMultipleExclusions(): void
    {
        $validator = new Glob(['feature/**', '!feature/wip', '!feature/experimental']);
        $this->assertTrue($validator->isValid('feature/foo'));
        $this->assertTrue($validator->isValid('feature/a/b'));
        $this->assertFalse($validator->isValid('feature/wip'));
        $this->assertFalse($validator->isValid('feature/experimental'));
        $this->assertFalse($validator->isValid('main'));
    }

    public function testMultipleInclusionsWithMultipleExclusions(): void
    {
        $validator = new Glob(['main', 'feature/**', '!feature/wip', '!feature/experimental']);
        $this->assertTrue($validator->isValid('main'));
        $this->assertTrue($validator->isValid('feature/foo'));
        $this->assertTrue($validator->isValid('feature/a/b'));
        $this->assertFalse($validator->isValid('feature/wip'));
        $this->assertFalse($validator->isValid('feature/experimental'));
        $this->assertFalse($validator->isValid('develop'));
    }

    public function testWildcardExclusionOverridesWildcardInclusion(): void
    {
        $validator = new Glob(['src/**', '!src/generated/**']);
        $this->assertTrue($validator->isValid('src/components/Button.php'));
        $this->assertTrue($validator->isValid('src/utils/helper.js'));
        $this->assertFalse($validator->isValid('src/generated/Foo.php'));
        $this->assertFalse($validator->isValid('src/generated/bar/Baz.php'));
        $this->assertFalse($validator->isValid('lib/other.php'));
    }

    public function testSpecificInclusionOverridesWildcardExclusion(): void
    {
        $validator = new Glob(['feature/hotfix/critical', '!feature/**']);
        $this->assertTrue($validator->isValid('feature/hotfix/critical')); // inclusion wins
        $this->assertFalse($validator->isValid('feature/foo'));
        $this->assertFalse($validator->isValid('feature/hotfix/other'));
        $this->assertFalse($validator->isValid('main'));
    }

    public function testOnlyExclusionsDefaultToTrueUnlessExcluded(): void
    {
        $validator = new Glob(['!main', '!develop']);
        $this->assertFalse($validator->isValid('main'));
        $this->assertFalse($validator->isValid('develop'));
        $this->assertTrue($validator->isValid('staging'));
        $this->assertTrue($validator->isValid('feature/foo'));
    }

    // -------------------------------------------------------------------------
    // Standalone wildcards
    // -------------------------------------------------------------------------

    public function testStarAloneMatchesSingleSegmentOnly(): void
    {
        $validator = new Glob(['*']);
        $this->assertTrue($validator->isValid('main'));
        $this->assertTrue($validator->isValid('develop'));
        $this->assertFalse($validator->isValid('feature/foo')); // * cannot cross /
        $this->assertFalse($validator->isValid('a/b/c'));
    }

    public function testDoubleStarAloneMatchesEverything(): void
    {
        $validator = new Glob(['**']);
        $this->assertTrue($validator->isValid('main'));
        $this->assertTrue($validator->isValid('feature/foo'));
        $this->assertTrue($validator->isValid('src/a/b/c/d/file.php'));
    }

    // -------------------------------------------------------------------------
    // Extension patterns — * scope vs. ** scope
    // -------------------------------------------------------------------------

    public function testStarDotExtMatchesRootLevelOnly(): void
    {
        $validator = new Glob(['*.php']);
        $this->assertTrue($validator->isValid('Foo.php'));
        $this->assertTrue($validator->isValid('index.php'));
        $this->assertFalse($validator->isValid('src/Foo.php')); // * does not cross /
        $this->assertFalse($validator->isValid('a/b/Foo.php'));
        $this->assertFalse($validator->isValid('Foo.js'));
    }

    public function testDoubleStarSlashExtMatchesAnyDepth(): void
    {
        $validator = new Glob(['**/*.php']);
        $this->assertTrue($validator->isValid('Foo.php'));
        $this->assertTrue($validator->isValid('src/Foo.php'));
        $this->assertTrue($validator->isValid('src/components/Foo.php'));
        $this->assertTrue($validator->isValid('a/b/c/d/Foo.php'));
        $this->assertFalse($validator->isValid('Foo.js'));
        $this->assertFalse($validator->isValid('src/Foo.js'));
    }

    public function testDirPrefixDoubleStarExtPattern(): void
    {
        $validator = new Glob(['src/**/*.php']);
        $this->assertTrue($validator->isValid('src/Foo.php'));
        $this->assertTrue($validator->isValid('src/components/Foo.php'));
        $this->assertTrue($validator->isValid('src/a/b/c/Foo.php'));
        $this->assertFalse($validator->isValid('Foo.php'));
        $this->assertFalse($validator->isValid('lib/Foo.php'));
        $this->assertFalse($validator->isValid('src/Foo.js'));
    }

    // -------------------------------------------------------------------------
    // Dots as literal characters
    // -------------------------------------------------------------------------

    public function testDotsInPatternAreLiteral(): void
    {
        $validator = new Glob(['release-1.0.0']);
        $this->assertTrue($validator->isValid('release-1.0.0'));
        $this->assertFalse($validator->isValid('release-1X0Y0'));
        $this->assertFalse($validator->isValid('release-1.0.0-hotfix'));
    }

    public function testVersionWildcardBranchPattern(): void
    {
        $validator = new Glob(['v*.*.*']);
        $this->assertTrue($validator->isValid('v1.2.3'));
        $this->assertTrue($validator->isValid('v10.20.30'));
        $this->assertTrue($validator->isValid('v1.2.3.4'));
        $this->assertFalse($validator->isValid('v1.2'));
        $this->assertFalse($validator->isValid('1.2.3'));
        $this->assertFalse($validator->isValid('v1/2/3'));
    }

    public function testDottedFilenamePattern(): void
    {
        $validator = new Glob(['*.test.js']);
        $this->assertTrue($validator->isValid('Button.test.js'));
        $this->assertTrue($validator->isValid('App.test.js'));
        $this->assertFalse($validator->isValid('ButtonXtestYjs'));
        $this->assertFalse($validator->isValid('src/Button.test.js'));
        $this->assertFalse($validator->isValid('Button.test.ts'));
    }

    // -------------------------------------------------------------------------
    // Prefix wildcard
    // -------------------------------------------------------------------------

    public function testPrefixWildcardBranchPattern(): void
    {
        $validator = new Glob(['main*']);
        $this->assertTrue($validator->isValid('main'));
        $this->assertTrue($validator->isValid('main-extra'));
        $this->assertTrue($validator->isValid('mainline'));
        $this->assertFalse($validator->isValid('main/branch'));
        $this->assertFalse($validator->isValid('develop'));
    }

    // -------------------------------------------------------------------------
    // Deep nesting
    // -------------------------------------------------------------------------

    public function testDoubleWildcardInMiddleDeepNesting(): void
    {
        $validator = new Glob(['a/**/b']);
        $this->assertTrue($validator->isValid('a/x/y/z/b'));
        $this->assertTrue($validator->isValid('a/p/q/r/s/b'));
        $this->assertTrue($validator->isValid('a/1/2/3/4/5/b'));
        $this->assertFalse($validator->isValid('a/x/y/z/b/extra'));
        $this->assertFalse($validator->isValid('prefix/a/x/b'));
    }

    public function testDoubleWildcardAtStartDeepNesting(): void
    {
        $validator = new Glob(['**/README.md']);
        $this->assertTrue($validator->isValid('README.md'));
        $this->assertTrue($validator->isValid('docs/README.md'));
        $this->assertTrue($validator->isValid('a/b/c/d/README.md'));
        $this->assertTrue($validator->isValid('x/y/z/w/v/README.md'));
        $this->assertFalse($validator->isValid('a/b/c/README.md.bak'));
        $this->assertFalse($validator->isValid('a/b/c/README.md/extra'));
    }

    // -------------------------------------------------------------------------
    // Real-world path patterns
    // -------------------------------------------------------------------------

    public function testGeneratedFilesAnywhereExclusion(): void
    {
        $validator = new Glob(['!**/generated/**']);
        $this->assertFalse($validator->isValid('generated/Foo.php'));
        $this->assertFalse($validator->isValid('src/generated/Foo.php'));
        $this->assertFalse($validator->isValid('src/api/generated/Bar.php'));
        $this->assertFalse($validator->isValid('generated/sub/deep/File.php'));
        $this->assertTrue($validator->isValid('src/components/Button.php'));
        $this->assertTrue($validator->isValid('main'));
    }

    public function testMultipleExtensionInclusions(): void
    {
        $validator = new Glob(['**/*.php', '**/*.js']);
        $this->assertTrue($validator->isValid('index.php'));
        $this->assertTrue($validator->isValid('src/App.php'));
        $this->assertTrue($validator->isValid('index.js'));
        $this->assertTrue($validator->isValid('src/components/App.js'));
        $this->assertFalse($validator->isValid('styles.css'));
        $this->assertFalse($validator->isValid('src/styles.css'));
        $this->assertFalse($validator->isValid('README.md'));
    }

    // -------------------------------------------------------------------------
    // Named-prefix single-level branch
    // -------------------------------------------------------------------------

    public function testReleaseBranchPattern(): void
    {
        $validator = new Glob(['release/*']);
        $this->assertTrue($validator->isValid('release/1.0'));
        $this->assertTrue($validator->isValid('release/hotfix'));
        $this->assertTrue($validator->isValid('release/2024-01-15'));
        $this->assertFalse($validator->isValid('release/1.0/patch'));
        $this->assertFalse($validator->isValid('release'));
        $this->assertFalse($validator->isValid('main'));
    }

    // -------------------------------------------------------------------------
    // Case sensitivity
    // -------------------------------------------------------------------------

    public function testPatternMatchingIsCaseSensitive(): void
    {
        $branchValidator = new Glob(['main']);
        $this->assertTrue($branchValidator->isValid('main'));
        $this->assertFalse($branchValidator->isValid('Main'));
        $this->assertFalse($branchValidator->isValid('MAIN'));

        $wildcardValidator = new Glob(['feature/*']);
        $this->assertTrue($wildcardValidator->isValid('feature/foo'));
        $this->assertFalse($wildcardValidator->isValid('Feature/foo'));
        $this->assertFalse($wildcardValidator->isValid('FEATURE/foo'));
    }

    // -------------------------------------------------------------------------
    // Character class patterns
    // -------------------------------------------------------------------------

    public function testCharacterClassInclusion(): void
    {
        $validator = new Glob(['[Mm]ain']);
        $this->assertTrue($validator->isValid('main'));
        $this->assertTrue($validator->isValid('Main'));
        $this->assertFalse($validator->isValid('MAIN'));
        $this->assertFalse($validator->isValid('develop'));
    }

    public function testCharacterClassInclusionWithWildcardExclusion(): void
    {
        // [Mm]ain is a character-class pattern (not a literal), so it must not
        // short-circuit before exclusions are evaluated.
        $validator = new Glob(['[Mm]ain', '!**']);
        $this->assertFalse($validator->isValid('main'));
        $this->assertFalse($validator->isValid('Main'));
    }

    public function testCharacterClassExclusion(): void
    {
        $validator = new Glob(['!feature/[0-9]*']);
        $this->assertFalse($validator->isValid('feature/123'));
        $this->assertFalse($validator->isValid('feature/9fix'));
        $this->assertTrue($validator->isValid('feature/abc'));
        $this->assertTrue($validator->isValid('main'));
    }

    // -------------------------------------------------------------------------
    // Metadata
    // -------------------------------------------------------------------------

    public function testValidatorMetadata(): void
    {
        $validator = new Glob([]);
        $this->assertFalse($validator->isArray());
        $this->assertSame(\Utopia\Validator::TYPE_STRING, $validator->getType());
        $this->assertNotEmpty($validator->getDescription());
    }

    public function testRejectsNonStringValues(): void
    {
        $validator = new Glob(['main']);
        $this->assertFalse($validator->isValid(123));
        $this->assertFalse($validator->isValid(null));
        $this->assertFalse($validator->isValid(['main']));
        $this->assertFalse($validator->isValid(true));
    }

    // -------------------------------------------------------------------------
    // Character class and escaped character coverage (gregpriday/gitignore-php parity)
    // -------------------------------------------------------------------------

    public function testLiteralsExact(): void
    {
        $this->assertTrue((new Glob(['file.txt']))->isValid('file.txt'));
        $this->assertFalse((new Glob(['file.txt']))->isValid('file.txt.bak'));
    }

    public function testSingleAsteriskDoesNotCrossSlash(): void
    {
        $this->assertTrue((new Glob(['*.txt']))->isValid('file.txt'));
        $this->assertTrue((new Glob(['*.txt']))->isValid('another.txt'));
        $this->assertFalse((new Glob(['*.txt']))->isValid('file.txt.bak'));
        $this->assertFalse((new Glob(['*.txt']))->isValid('dir/file.txt')); // * does not cross /
    }

    public function testQuestionMarkSingleChar(): void
    {
        $this->assertTrue((new Glob(['file.?xt']))->isValid('file.txt'));
        $this->assertTrue((new Glob(['file.?xt']))->isValid('file.dxt'));
        $this->assertFalse((new Glob(['file.?xt']))->isValid('file.xtt'));
    }

    public function testDoubleStarPrefixFileMatch(): void
    {
        $this->assertTrue((new Glob(['**/file.txt']))->isValid('file.txt'));
        $this->assertTrue((new Glob(['**/file.txt']))->isValid('dir/file.txt'));
        $this->assertTrue((new Glob(['**/file.txt']))->isValid('dir/subdir/file.txt'));
        $this->assertFalse((new Glob(['**/file.txt']))->isValid('file.txt.bak'));
    }

    public function testDoubleStarMiddleFileMatch(): void
    {
        $this->assertTrue((new Glob(['src/**/file.txt']))->isValid('src/file.txt'));
        $this->assertTrue((new Glob(['src/**/file.txt']))->isValid('src/dir/file.txt'));
        $this->assertTrue((new Glob(['src/**/file.txt']))->isValid('src/dir/subdir/file.txt'));
        $this->assertFalse((new Glob(['src/**/file.txt']))->isValid('other/file.txt'));
    }

    public function testEscapedAsteriskIsLiteral(): void
    {
        $this->assertTrue((new Glob(['file\*.txt']))->isValid('file*.txt'));
        $this->assertFalse((new Glob(['file\*.txt']))->isValid('fileX.txt'));
    }

    public function testBasicCharacterClasses(): void
    {
        $this->assertTrue((new Glob(['[a]bc.txt']))->isValid('abc.txt'));
        $this->assertFalse((new Glob(['[a]bc.txt']))->isValid('bbc.txt'));
        $this->assertTrue((new Glob(['[a-z]est.txt']))->isValid('test.txt'));
        $this->assertFalse((new Glob(['[a-z]est.txt']))->isValid('Test.txt'));
        $this->assertTrue((new Glob(['[A-Z]est.txt']))->isValid('Test.txt'));
        $this->assertFalse((new Glob(['[A-Z]est.txt']))->isValid('test.txt'));
        $this->assertTrue((new Glob(['file[0-9].log']))->isValid('file5.log'));
        $this->assertFalse((new Glob(['file[0-9].log']))->isValid('fileA.log'));
        $this->assertTrue((new Glob(['[a-zA-Z]file.txt']))->isValid('afile.txt'));
        $this->assertTrue((new Glob(['[a-zA-Z]file.txt']))->isValid('Afile.txt'));
        $this->assertFalse((new Glob(['[a-zA-Z]file.txt']))->isValid('1file.txt'));
    }

    public function testNegatedCharacterClasses(): void
    {
        $this->assertTrue((new Glob(['[!a-z]file.txt']))->isValid('Afile.txt'));
        $this->assertFalse((new Glob(['[!a-z]file.txt']))->isValid('afile.txt'));
        $this->assertTrue((new Glob(['^[^a-z]file.txt']))->isValid('^1file.txt'));
        $this->assertFalse((new Glob(['[^a-z]file.txt']))->isValid('afile.txt'));
        $this->assertTrue((new Glob(['[!a-z0-9]file.txt']))->isValid('#file.txt'));
        $this->assertFalse((new Glob(['[!a-z0-9]file.txt']))->isValid('afile.txt'));
        $this->assertFalse((new Glob(['[!a-z0-9]file.txt']))->isValid('5file.txt'));
    }

    public function testCaretNegatedCharacterClass(): void
    {
        $this->assertTrue((new Glob(['[^a-z]file.txt']))->isValid('1file.txt'));
        $this->assertFalse((new Glob(['[^a-z]file.txt']))->isValid('afile.txt'));
    }

    public function testSpecialCharsInsideCharacterClasses(): void
    {
        $this->assertTrue((new Glob(['file[.+]name.txt']))->isValid('file.name.txt'));
        $this->assertTrue((new Glob(['file[.+]name.txt']))->isValid('file+name.txt'));
        $this->assertFalse((new Glob(['file[.+]name.txt']))->isValid('filename.txt'));
        $this->assertTrue((new Glob(['[_!@#]special.txt']))->isValid('_special.txt'));
        $this->assertTrue((new Glob(['[_!@#]special.txt']))->isValid('@special.txt'));
        $this->assertFalse((new Glob(['[_!@#]special.txt']))->isValid('xspecial.txt'));
        $this->assertTrue((new Glob(['[-abc]dash.txt']))->isValid('-dash.txt'));
        $this->assertTrue((new Glob(['[-abc]dash.txt']))->isValid('adash.txt'));
        $this->assertTrue((new Glob(['[abc-]dash.txt']))->isValid('-dash.txt'));
    }

    public function testCharacterClassCombinedWithGlobstar(): void
    {
        $this->assertTrue((new Glob(['[a-z]*.txt']))->isValid('abc.txt'));
        $this->assertFalse((new Glob(['[a-z]*.txt']))->isValid('Abc.txt'));
        $this->assertTrue((new Glob(['**/[a-z]*.txt']))->isValid('dir/abc.txt'));
        $this->assertTrue((new Glob(['**/[a-z]*.txt']))->isValid('dir/subdir/abc.txt'));
        $this->assertFalse((new Glob(['**/[a-z]*.txt']))->isValid('dir/Abc.txt'));
        $this->assertTrue((new Glob(['[a-z][0-9]*.txt']))->isValid('a1file.txt'));
        $this->assertFalse((new Glob(['[a-z][0-9]*.txt']))->isValid('ab.txt'));
        $this->assertFalse((new Glob(['[a-z][0-9]*.txt']))->isValid('A1file.txt'));
    }

    public function testEdgeCaseEmptyCharacterClass(): void
    {
        // Empty character class [] matches nothing
        $this->assertFalse((new Glob(['[]file.txt']))->isValid('file.txt'));
    }

    public function testEdgeCaseUnclosedBracket(): void
    {
        // Unclosed bracket treated as literal
        $this->assertTrue((new Glob(['[abc']))->isValid('[abc'));
        $this->assertFalse((new Glob(['[abc']))->isValid('abc'));
    }
}
