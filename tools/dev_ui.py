"""Minimal test UI placeholder for Like-sprint."""

import gradio as gr


def ping() -> str:
    return "Like-sprint autotest UI is ready"


with gr.Blocks(title="Like-sprint Autotest UI") as demo:
    gr.Markdown("# Like-sprint Autotest UI")
    btn = gr.Button("Ping")
    out = gr.Textbox(label="Result")
    btn.click(fn=ping, outputs=out)


if __name__ == "__main__":
    demo.launch(server_name="127.0.0.1", server_port=7861)
