---
name: Release Add-on (team only)
about: Describes default checklist for the plugin's add-on release process.
title: Release [ADD-ON] version [VERSION]
labels: release
assignees: ''

---

To release the add-on plugin, please make sure to check all the checkboxes below.

### Pre-release Checklist

- [ ] Create a new branch `release/<VERSION>` based on `development`
- [ ] Run `composer update --no-dev` and check if there is any relevant update. Check if you need to lock the current version for any dependency. Commit the changes.
- [ ] Update the version number to the next stable version. Use `$ phing set-version` and commit
- [ ] Update the changelog - make sure all the changes are there with a user-friendly description and commit
- [ ] Pull the local branch to the origin with the same name `release/<VERSION>`
- [ ] Create a Pull Request to the `master` branch and ask for review
- [ ] Build the zip using `$ phing build`
- [ ] Send to the team for testing

### Release Checklist

- [ ] Merge the Pull Request into the `master` branch
- [ ] Create the release in Github (make sure it is based on the `master` branch and has a correct tag name - version number without prefix)
- [ ] Update EDD registry and upload the new package
- [ ] Make the final test updating the plugin in a staging site
