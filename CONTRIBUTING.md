Contribution Guidelines
=======================

First of all, each single contribution is appreciated, whether a typo fix,
improved documentation, a fixed bug or a whole new feature.

## Making your changes

 1. Fork the repository on GitHub
 2. Create a topic branch with a descriptive name, e.g. `fix-issue-123` or
    `feature-x`
 3. Make your modifications, complying with the
    [code conventions](#code-conventions)
 4. Commit small logical changes, each with a descriptive commit message.
    Please don't mix unrelated changes in a single commit.

## Commit messages

Please format your commit messages as follows:

    Short summary of the change (up to 50 characters)

    Optionally add a more extensive description of your change after a
    blank line. Wrap the lines in this and the following paragraphs after
    72 characters.

## Submitting your changes

 1. Push your changes to a topic branch in your fork of the repository.
 2. [Submit a pull request][pr] to the original repository.
    Describe your changes as short as possible, but as detailed as needed for
    others to get an overview of your modifications.
 3. *Optionally*, [open an issue][issue] in the meta-repository if your change
    might be relevant to other implementations of Steam Condenser. Please add a
    link to your pull request.

## Code conventions

 * White spaces:
   * Indent using 4 spaces
   * Line endings must be line feeds (\n)
   * Add a newline at end of file
 * Name conventions:
   * `UpperCamelCase` for classes
   * `lowerCamelCase` for fields, methods and variables
   * `UPPER_CASE` for constants

## Further information

 * [General GitHub documentation][gh-help]
 * [GitHub pull request documentation][gh-pr]
 * [Google group][mail]
 * \#steam-condenser on freenode.net

 [gh-help]: https://help.github.com
 [gh-pr]:   https://help.github.com/send-pull-requests
 [issue]:   https://github.com/koraktor/steam-condenser/issues/new
 [mail]:    https://groups.google.com/group/steam-condenser
 [pr]:      https://github.com/koraktor/steam-condenser-php/pull/new
