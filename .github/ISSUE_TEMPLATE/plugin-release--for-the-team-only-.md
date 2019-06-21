---
name: Plugin Release [for the team only]
about: Describes default checklist for any plugin's release
title: Release UpStream
labels: release
assignees: ''

---

To release the plugin, please make sure to check all the checkboxes below.

### Checklist

- [ ] Run composer update --no-dev
- [ ] Commit changes
- [ ] Update changelog
- [ ] Update version number to a stable version
- [ ] Pull to the development repo
- [ ] Send for testing
- [ ] Create and merge Pull Request to merge into Master
- [ ] Build the zip
- [ ] Create Github release
- [ ] Cleanup svn/trunk
- [ ] Unzip the package and move files to the svn/trunk
- [ ] Commit the changes on trunk
- [ ] Copy svn/trunk into svn/tags...
- [ ] Commit the new tag
- [ ] Add an item
