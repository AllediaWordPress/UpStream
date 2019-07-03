---
name: Release Add-on (team only)
about: Describes default checklist for the plugin's add-on release process.
title: Release [ADD-ON] version [VERSION]
labels: release
assignees: ''

---

To release the add-on plugin, please make sure to check all the checkboxes below.

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

### Publish on the site
- [ ] Update EDD registry and upload the new package
- [ ] Make the final test updating the plugin in a staging site
