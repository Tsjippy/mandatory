import { __ } from "@wordpress/i18n";
import { registerPlugin } from "@wordpress/plugins";
import { CheckboxControl } from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { PluginDocumentSettingPanel } from "@wordpress/editor";
import { useEntityProp } from "@wordpress/core-data";
import apiFetch from "@wordpress/api-fetch";
import { addQueryArgs } from "@wordpress/url";
import { useState, useEffect } from "@wordpress/element";

const Component = () => {
  const postType = useSelect(
    (select) => select("core/editor").getCurrentPostType(),
    [],
  );
  const [meta, setMeta] = useEntityProp("postType", postType, "meta");

  if (meta == undefined) {
    return "";
  }

  const postId = wp.data.select("core/editor").getCurrentPostId();

  // Define a variable and a function to update that variable
  const [audienceOptions, setAudienceOptions] = useState([]);

  // Fetch the audience options
  // Do so only on the first render
  useEffect(() => {
    apiFetch({
      path: tsjippy.restApiPrefix + `/mandatory_content/get_audience_options`,
      method: "POST",
      data: { post_id: postId },
    }).then((res) => {
      setAudienceOptions(res);
    });
  }, []);

  const audience = meta["tsjippy_audience"];

  const updateMetaValue = (selected, key) => {
    let newMeta = { ...meta };

    let newAudience = [...audience];
    // add a new value
    if (selected) {
      newAudience.push(key);
      // value removed
    } else {
      const index = newAudience.indexOf(key);
      if (index > -1) {
        // only splice array when item is found
        newAudience.splice(index, 1); // 2nd parameter means remove one item only
      }
    }

    newMeta["tsjippy_audience"] = newAudience;

    setMeta(newMeta);
  };

  const CheckBoxes = () => {
    if (!audienceOptions) {
      return <p {...useBlockProps()}>{__("Loading...", "tsjippy")}</p>;
    }

    return Object.keys(audienceOptions).map((index) => (
      <CheckboxControl
        key={index}
        label={audienceOptions[index]}
        onChange={(selected) => updateMetaValue(selected, index)}
        checked={audience.indexOf(index) > -1}
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
};

registerPlugin("mandatory-audience", {
  render: Component,
  icon: "groups",
});
