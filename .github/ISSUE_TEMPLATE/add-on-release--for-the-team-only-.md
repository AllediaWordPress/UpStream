---
name: Add-on Release [for the team only]
about: Describes default checklist for an add-on plugin release
title: Release add-on
labels: release
assignees: ''

---

To release the add-on plugin, please make sure to check all the checkboxes below.

### Checklist

- [ ] Run `composer update --no-dev` and check if there is any relevant update. Check if you need to lock the current version for any dependency.
- [ ] Commit changes to the `development` branch
- [ ] Update the changelog - make sure all the changes are there with a user-friendly description
- [ ] Update the version number to the next stable version. Use `$ phing set-version`
- [ ] Pull to the `development` branch
- [ ] Send to the team for testing
- [ ] Merge `development` into the `master` branch
- [ ] Build the zip using `$ phing build`
- [ ] Create the Github release (make sure it is based on the `master` branch)
- [ ] Update EDD registry and upload the new package
