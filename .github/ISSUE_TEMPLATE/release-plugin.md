---
name: Release UpStream (team only)
about: Describes default checklist for the plugin's release process.
title: Release UpStream version [VERSION]
labels: release
assignees: ''

---

To release the plugin, please make sure to check all the checkboxes below.

### Pre-release Checklist

- [ ] Create a new release branch *release/VERSION* based on *development*: `$ git flow release start VERSION`
- [ ] Run `composer update --no-dev` and check if there is any relevant update. Check if you need to lock the current version for any dependency. Commit the changes.
- [ ] Update the version number to the next stable version. Use `$ phing set-version` and commit
- [ ] Update the changelog - make sure all the changes are there with a user-friendly description and commit
- [ ] Build the zip using `$ phing build`
- [ ] Send to the team for testing

### Release Checklist

After all the tests are passing:

- [ ] Finish the release running: `$ git flow release finish VERSION`
- [ ] Create the release in Github (make sure it has the correct tag name - version number without prefix)

#### SVN Repo
- [ ] Cleanup the `trunk` directory.
- [ ] Unzip the built package and move files to the `trunk`
- [ ] Remove any eventual file that shouldn't be released in the package (if you find anything, make sure to create an issue to fix the build script)
- [ ] Look for new files `$ svn status | grep \?` and add them using `$ svn add <each_file_path>`
- [ ] Look for removed files `$ svn status | grep !` and remove them `$ svn rm <each_file_path>`
- [ ] Create the new tag `$ svn cp trunk tags/<version>`
- [ ] Commit the changes `$ svn ci -m 'Releasing <version>'`
- [ ] Wait until WordPress updates the version number and make the final test updating the plugin in a staging site
