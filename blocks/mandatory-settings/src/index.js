const { __ } = wp.i18n;
const { registerPlugin } = wp.plugins;
import { CheckboxControl } from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useEntityProp } from "@wordpress/core-data";

registerPlugin("mandatory-audience", {
  render: function () {
    const postType = useSelect(
      (select) => select("core/editor").getCurrentPostType(),
      [],
    );
    const [meta, setMeta] = useEntityProp("postType", postType, "meta");

    if (meta == undefined) {
      return "";
    }

    const audience = meta["tsjippy_audience"] == undefined ? {} : JSON.parse(meta["tsjippy_audience"]);

    const updateMetaValue = (selected, key) => {
      let newMeta = { ...meta };

      let newAudience = { ...audience };
      // add a new value
      if (selected) {
        newAudience[key] = key;
        // value removed
      } else {
        delete newAudience[key];
      }

      newMeta["tsjippy_audience"] = JSON.stringify(newAudience);

      setMeta(newMeta);
    };

    const CheckBoxes = () => {
      return Object.keys(mandatory).map((index) => (
        <CheckboxControl
          key={index}
          label={mandatory[index]}
          onChange={(selected) => updateMetaValue(selected, index)}
          checked={audience[index] != undefined}
        />
      ));
    };

    return (
      <PluginDocumentSettingPanel
        name="mandatory-audience"
        title={__("Mandatory settings", "tsjippy")}
        className="mandatory-audience"
      >
        {CheckBoxes()}
      </PluginDocumentSettingPanel>
    );
  },
  icon: "groups",
});
