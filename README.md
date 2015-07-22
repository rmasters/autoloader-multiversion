# An attempt at autoloading multiple versions of the same package

This is an experimental prototype of an autoloader that can handle cases where
multiple packages are installed that depend on different versions of the same
package.

When looking at this problem, the initial problems encountered are:

*   PHP does not allow multiple definitions of the same class
*   When you have two versions of a class, how do you know which to load, given
    the context? (i.e. How does package A know to load version 1 of package C
    and not version 2?)
*   How do you specifically load the alternative class?

This solution pre-processes declared classes, such that:

*   Packages are stored in directories that indicate their version: e.g.
    `vendor/package@1.0`,
*   Each package is assumed to have a root namespace, `Vendor\Package`,
*   Each class in that package is cloned into a new namespace:
    `Vendor\Package\v1_0\Class`,
*   Each reference to the package is rewritten to use the versioned-namespace,
*   For user-friendliness, 'root' dependencies (i.e. those required by the
    application) are aliased to their versioned counterparts with `class_alias`.

## Example

In this implementation, we have three packages:

*   Rmasters\Reverse, which depends on Rmasters\Filter v1.0
*   Rmasters\Rotate, which depends on Rmasters\Filter v2.0

>   Each package declares it's own classmap.php (an array of FQCNs to paths).
>   This is only an implementation detail, a real package manager would
>   generate classmaps themselves.

test.php imports both packages (using their root-package aliases), which
dynamically rewrite the Filter classes to be versioned, and the Reverse/Rotate
classes to reference the two versions.

## Further improvements

*   The autoloader shouldn't regenerate cached classes each time.
*   In fact, root dependencies, and those with only one version installed,
    don't need version copies.
*   A better solution might be to do this versioning on checkout, rather than
    autoload - but then you lose the ability to make easy edits.

## Drawbacks

*   Versioned packages can't be easily edited, without regenerating the cached
    classes. This could be mitigated with proxy classes, if PHP had a method of
    unloading classes, a method of renaming classes, or a non-global class
    table.
